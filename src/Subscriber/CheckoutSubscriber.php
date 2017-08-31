<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Avalara\DocumentType;
use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class CheckoutSubscriber extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onBeforeCheckoutConfirm',
            'sOrder::sSaveOrder::before' => 'onBeforeSOrderSaveOrder',
            'sOrder::sSaveOrder::after' => 'onAfterSOrderSaveOrder',
            'sAdmin::sGetPremiumDispatch::after' => 'onAfterAdminGetPremiumDispatch',
            'sBasket::sGetBasket::before' => 'onBeforeSBasketSGetBasket',
            'sBasket::sAddVoucher::before' => 'onBeforeBasketSAddVoucher',
        ];
    }

    /**
     * call getTax service
     * @param \Enlight_Event_EventArgs $args
     */
    public function onBeforeCheckoutConfirm(\Enlight_Event_EventArgs $args)
    {
        $action = $args->getRequest()->getActionName();
        // Trigger this subscriber only on 'confirm' action
        if ('confirm' !== $action) {
            return;
        }
        
        $session = $this->getSession();
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');

        try {
            /* @var $model \Avalara\CreateTransactionModel */
            $model = $adapter->getFactory('OrderTransactionModelFactory')->build();

            if (!$service->isGetTaxCallAvailable($model, $this->getSession()) || empty($args->getSubject()->View()->sUserLoggedIn)) {
                $adapter->getLogger()->info('GetTax call for current basket already done / not enabled.');
                return;
            }
            
            $adapter->getLogger()->info('GetTax for current basket.');
            $response = $service->calculate($model);
            
            $session->MoptAvalaraGetTaxResult = $service->generateTaxResultFromResponse($response);
            $session->MoptAvalaraGetTaxRequestHash = $service->getHashFromRequest($model);
        } catch (\Exception $e) {
            $adapter->getLogger()->error('GetTax call failed: ' . $e->getMessage());
        }
        
        //Recall controller so basket tax and landedCost could be applied
        $args->getSubject()->forward('confirm');
        return;
    }

    /**
     * check if current basket matches with previous avalara call (e.g. multi tab)
     * @param \Enlight_Event_EventArgs $args
     */
    public function onBeforeSOrderSaveOrder(\Enlight_Hook_HookArgs $args)
    {
        $adapter = $this->getAdapter();
        $getTaxCommitRequest = $this->validateCommitCall();
        if (!$getTaxCommitRequest) {
            $adapter->getLogger()->error('Not in avalara context');
            return $args->getReturn();
        }
        $this->getSession()->MoptAvalaraGetTaxCommitRequest = $getTaxCommitRequest;
        //set all basket items' taxId to 0 for custom taxrates in backend etc.
        foreach ($args->getSubject()->sBasketData["content"] as &$basketRow) {
            $basketRow["taxId"] = 0;
            $basketRow["taxID"] = 0;
        }
        
        return $args->getReturn();
    }

    /**
     * validate commit call with previous getTax call
     * @return \Avalara\CreateTransactionModel | bool
     */
    protected function validateCommitCall()
    {
        $session = $this->getSession();
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');

        //proceed if no sales order call was made
        if (!$session->MoptAvalaraGetTaxRequestHash) {
            $adapter->getLogger()->debug('No sales order call was made.');
            return null;
        }

        /* @var $model \Avalara\CreateTransactionModel */
        $model = $adapter->getFactory('OrderTransactionModelFactory')->build();
        $adapter->getLogger()->debug('validateCommitCall...');
        //prevent parent execution on request mismatch
        if ($session->MoptAvalaraGetTaxRequestHash != $service->getHashFromRequest($model)) {
            $adapter->getLogger()->error('Mismatching requests, do not proceed.');
            throw new \Exception('MoptAvalara: mismatching requests, do not proceed.');
        }

        $adapter->getLogger()->debug('Matching requests, proceed...', [$model]);
        
        return $model;
    }

    /**
     * commit transaction
     * @param \Enlight_Event_EventArgs $args
     * @return type
     */
    public function onAfterSOrderSaveOrder(\Enlight_Hook_HookArgs $args)
    {
        $adapter = $this->getAdapter();
        $adapter->getLogger()->debug('onAfterSOrderSaveOrder call');
        $session = $this->getSession();
        if (!$orderNumber = $args->getReturn()) {
            $adapter->getLogger()->debug('orderNumber did not exist');
            return;
        }

        if (!$taxRequest = $session->MoptAvalaraGetTaxCommitRequest) {
            $adapter->getLogger()->debug('MoptAvalaraGetTaxCommitRequest is empty');
            return;
        }

        if (!$taxResult = $session->MoptAvalaraGetTaxResult) {
            $adapter->getLogger()->debug('MoptAvalaraGetTaxResult is empty');
            return;
        }

        if (!$order = $adapter->getOrderByNumber($orderNumber)) {
            $msg = 'There is no order with number: ' . $orderNumber;
            $this->getAdapter()->getLogger()->critical($msg);
            throw new \Exception($msg);
        }
        $this->setOrderAttributes($order, $taxRequest, $taxResult);
        
        unset($session->MoptAvalaraGetTaxRequestHash);
        unset($session->MoptAvalaraGetTaxCommitRequest);
        unset($session->MoptAvalaraGetTaxResult);
    }

    /**
     *
     * @param \Enlight_Hook_HookArgs $args
     * @return void
     */
    public function onAfterAdminGetPremiumDispatch(\Enlight_Hook_HookArgs $args)
    {
        $return = $args->getReturn();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        $session = $this->getSession();
        if (empty($session->MoptAvalaraGetTaxResult) || !$service->isGetTaxEnabled()) {
            return;
        }

        $taxRate = $service->getTaxRateForOrderBasketId($session->MoptAvalaraGetTaxResult, ShippingFactory::ARTICLE_ID);
        
        if (!$taxRate) {
            return $return;
        }
        $return['tax_calculation'] = true;
        $return['tax_calculation_value'] = $taxRate;
        
        return $return;
    }
    
    /**
     * set taxrate for discounts
     * @param \Enlight_Hook_HookArgs $args
     * @return type
     */
    public function onBeforeSBasketSGetBasket(\Enlight_Hook_HookArgs $args)
    {
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        $session = $this->getSession();
        if (empty($session->MoptAvalaraGetTaxResult) || !$service->isGetTaxEnabled()) {
            return;
        }
        //abfangen voucher mode==2 strict=1 => eigene TaxRate zuweisen aus Avalara Response
        $taxResult = $session->MoptAvalaraGetTaxResult;
        if (!((float)$taxResult->totalTaxable)) {
            return;
        }
        
        $taxRate = bcdiv((float)$taxResult->totalTax, (float)$taxResult->totalTaxable, AvalaraSDKAdapter::BCMATH_SCALE);

        $config = Shopware()->Config();
        $config['sDISCOUNTTAX'] = bcmul($taxRate, 100, AvalaraSDKAdapter::BCMATH_SCALE);
        $config['sTAXAUTOMODE'] = false;
    }
    
    /**
     *
     * @param \Enlight_Hook_HookArgs $args
     * @return void
     */
    public function onBeforeBasketSAddVoucher(\Enlight_Hook_HookArgs $args)
    {
        $session = $this->getSession();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        if (empty($session->MoptAvalaraGetTaxResult) || !$service->isGetTaxEnabled()) {
            return;
        }
        
        $voucherCode = strtolower(stripslashes($args->get('voucherCode')));

        // Load the voucher details
        $voucherDetails = Shopware()->Db()->fetchRow(
            'SELECT *
              FROM s_emarketing_vouchers
              WHERE modus != 1
              AND LOWER(vouchercode) = ?
              AND (
                (valid_to >= now() AND valid_from <= now())
                OR valid_to IS NULL
              )',
            [$voucherCode]
        ) ? : [];

        if (empty($voucherDetails['strict'])) {
            return;
        }
        
        //get tax rate for voucher
        $taxRate = $service->getTaxRateForOrderBasketId($session->MoptAvalaraGetTaxResult, LineFactory::ARTICLEID_VOUCHER);
        if (!$taxRate) {
            return;
        }
        $config = Shopware()->Config();
        $config['sVOUCHERTAX'] = $taxRate;
    }
    
    /**
     * 
     * @param \Shopware\Models\Order\Order $order
     * @param \stdClass $taxRequest
     * @param \stdClass $taxResult
     */
    private function setOrderAttributes(\Shopware\Models\Order\Order $order, $taxRequest, $taxResult)
    {
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        if (!$service->isGetTaxEnabled()) {
            return;
        }
        
        $incoterms = isset($taxRequest->parameters->{LandedCostRequestParams::LANDED_COST_INCOTERMS})
            ? $taxRequest->parameters->{LandedCostRequestParams::LANDED_COST_INCOTERMS}
            : null
        ;

        $session = $this->getSession();
        $landedCost = $service->getLandedCost($session->MoptAvalaraGetTaxResult);
        $insurance = $service->getInsuranceCost($taxResult);

        $order->getAttribute()->setMoptAvalaraTransactionType(DocumentType::C_SALESORDER);
        $order->getAttribute()->setMoptAvalaraIncoterms($incoterms);
        $order->getAttribute()->setMoptAvalaraLandedcost($landedCost);
        $order->getAttribute()->setMoptAvalaraInsurance($insurance);
        
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
}
