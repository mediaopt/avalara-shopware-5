<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Avalara\DocumentType;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;

class CheckoutSubscriber extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     *
     * @return array
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
        $session = $this->getSession();
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        $action = $args->getRequest()->getActionName();
        // Do not call Avalara in ajaxCart - we do not have all information yet!
        if (in_array($action, ['ajaxAmount', 'ajaxCart'])) {
            return;
        }
        
        try {
            /* @var $model \Avalara\CreateTransactionModel */
            $model = $adapter->getFactory('OrderTransactionModelFactory')->build();

            if (!$service->isGetTaxCallAvalible($model, $this->getSession()) || empty($args->getSubject()->View()->sUserLoggedIn)) {
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
        return false;
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
            return $args->getReturn();
        }

        if (!$taxRequest = $session->MoptAvalaraGetTaxCommitRequest) {
            $adapter->getLogger()->debug('MoptAvalaragetTaxCommitrequest is empty');
            return $args->getReturn();
        }

        if (!$order = $this->getOrderByNumber($orderNumber)) {
            $msg = 'There is no order with number: ' . $orderNumber;
            $this->getAdapter()->getLogger()->critical($msg);
            throw new \Exception($msg);
        }
        $this->setOrderAttributes($order, $taxRequest);
        
        unset($session->MoptAvalaraGetTaxRequestHash);
        unset($session->MoptAvalaraGetTaxCommitRequest);
        unset($session->MoptAvalaraGetTaxResult);
        
        return $args->getReturn();
    }

    /**
     *
     * @param \Enlight_Hook_HookArgs $args
     * @return void
     */
    public function onAfterAdminGetPremiumDispatch(\Enlight_Hook_HookArgs $args)
    {
        $return = $args->getReturn();
        
        $session = $this->getSession();
        if (empty($session->MoptAvalaraGetTaxResult)) {
            return;
        }

        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        $taxRate = $service->getTaxRateForOrderBasketId($session->MoptAvalaraGetTaxResult, ShippingFactory::ARTICLE_ID);
        
        if (!$taxRate) {
            return;
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
        $session = $this->getSession();
        if (empty($session->MoptAvalaraGetTaxResult)) {
            return;
        }

        if (!$session->MoptAvalaraGetTaxResult->totalTaxable) {
            return;
        }
        //abfangen voucher mode==2 strict=1 => eigene TaxRate zuweisen aus Avalara Response
        $taxRate = ((float)$session->MoptAvalaraGetTaxResult->totalTax / (float)$session->MoptAvalaraGetTaxResult->totalTaxable) * 100;

        $config = Shopware()->Config();
        $config['sDISCOUNTTAX'] = number_format($taxRate, 2);
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
        if (empty($session->MoptAvalaraGetTaxResult)) {
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
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        $taxRate = $service->getTaxRateForOrderBasketId($session->MoptAvalaraGetTaxResult, LineFactory::ARTICLEID_VOUCHER);
        
        $config = Shopware()->Config();
        $config['sVOUCHERTAX'] = $taxRate;
    }
    
    /**
     * 
     * @param \Shopware\Models\Order\Order $order
     * @param \stdClass $taxRequest
     */
    private function setOrderAttributes(\Shopware\Models\Order\Order $order, $taxRequest)
    {
        $incoterms = isset($taxRequest->parameters->{LandedCostRequestParams::LANDED_COST_INCOTERMS})
            ? $taxRequest->parameters->{LandedCostRequestParams::LANDED_COST_INCOTERMS}
            : null
        ;
        $order->getAttribute()->setMoptAvalaraTransactionType(DocumentType::C_SALESORDER);
        $order->getAttribute()->setMoptAvalaraExemptionCode($taxRequest->exemptionNo);
        $order->getAttribute()->setMoptAvalaraIncoterms($incoterms);
        
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
}
