<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Avalara\CreateTransactionModel;
use Avalara\DocumentType;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\InsuranceFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;

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
            'Enlight_Controller_Action_Frontend_Checkout_Confirm' => 'onBeforeCheckoutConfirm',
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

        /* @var $model \Avalara\CreateTransactionModel */
        $model = $adapter->getFactory('OrderTransactionModelFactory')->build();

        if (!$this->isGetTaxCallAvalible($model) || empty($args->getSubject()->View()->sUserLoggedIn)) {
            $adapter->getLogger()->info('GetTax call for current basket already done / not enabled.');
            return;
        }

        try {
            $adapter->getLogger()->info('GetTax for current basket.');

            /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
            $service = $adapter->getService('GetTax');
            $response = $service->calculate($model);

            $session->MoptAvalaraGetTaxResult = $this->generateTaxResultFromResponse($response);
            $session->MoptAvalaraGetTaxRequestHash = $this->getHashFromRequest($model);
        } catch (\Exception $e) {
            $adapter->getLogger()->error('GetTax call failed: ' . $e->getMessage());
            $args->getSubject()->forward('checkout', 'index');
            
            return true;
        }
    }

    /**
     * check if getTax call has to be made
     * @param \Avalara\CreateTransactionModel $model
     * @return boolean
     * @todo: check country (?)
     */
    protected function isGetTaxCallAvalible(CreateTransactionModel $model)
    {
        $taxEnabled = $this
            ->getAdapter()
            ->getPluginConfig(Form::TAX_ENABLED_FIELD)
        ;
        if (!$taxEnabled) {
            return false;
        }


        $session = $this->getSession();
        if (!$session->MoptAvalaraGetTaxResult || !$session->MoptAvalaraGetTaxRequestHash) {
            return true;
        }

        if ($session->MoptAvalaraGetTaxRequestHash !== $this->getHashFromRequest($model)) {
            return true;
        }

        return false;
    }

    /**
     * check if current basket matches with previous avalara call (e.g. multi tab)
     * @param \Enlight_Event_EventArgs $args
     */
    public function onBeforeSOrderSaveOrder(\Enlight_Hook_HookArgs $args)
    {
        $getTaxCommitRequest = $this->validateCommitCall();
        if (!($getTaxCommitRequest instanceof CreateTransactionModel)) {
            $adapter->getLogger()->error('Not in avalara context');
            return;
        }
        Shopware()->Session()->MoptAvalaraGetTaxCommitRequest = $getTaxCommitRequest;
        //set all basket items' taxId to 0 for custom taxrates in backend etc.
        foreach ($args->getSubject()->sBasketData["content"] as &$basketRow) {
            $basketRow["taxID"] = 0;
        }
    }

    /**
     * validate commit call with previous getTax call
     * @return \Avalara\CreateTransactionModel | bool
     */
    protected function validateCommitCall()
    {
        $session = $this->getSession();
        $adapter = $this->getAdapter();

        //proceed if no sales order call was made
        if (!$session->MoptAvalaraGetTaxRequestHash) {
            $adapter->getLogger()->debug('No sales order call was made.');
            return true;
        }

        /* @var $model \Avalara\CreateTransactionModel */
        $model = $adapter->getFactory('OrderTransactionModelFactory')->build();
        $adapter->getLogger()->info('validateCommitCall...');
        //prevent parent execution on request mismatch
        if ($session->MoptAvalaraGetTaxRequestHash != $this->getHashFromRequest($model)) {
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
        $adapter->getLogger()->info('onAfterSOrderSaveOrder call');
        if (!$orderNumber = $args->getReturn()) {
            $adapter->getLogger()->debug('orderNumber did not exist');
            return;
        }

        /*@var \Avalara\CreateTransactionModel $model */
        if (!$model = Shopware()->Session()->MoptAvalaraGetTaxCommitRequest) {
            $adapter->getLogger()->debug('MoptAvalaragetTaxCommitrequest is empty');
            return;
        }

        if (!$order = $this->getOrderById($orderNumber)) {
            $msg = 'There is no order with number: ' . $id;
            $this->getAdapter()->getLogger()->critical($msg);
            throw new \Exception($msg);
        }
        $order->getAttribute()->setMoptAvalaraTransactionType(DocumentType::C_SALESORDER);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
        
        unset(Shopware()->Session()->MoptAvalaraGetTaxRequestHash);
        unset(Shopware()->Session()->MoptAvalaraGetTaxCommitRequest);
        unset(Shopware()->Session()->MoptAvalaraGetTaxResult);
    }

    /**
     * get hash from request to compare calculate & commit call
     * unset changing fields during both calls
     *
     * @param \Avalara\CreateTransactionModel $model
     * @return string
     */
    protected function getHashFromRequest(CreateTransactionModel $model)
    {
        $data = $this->objectToArray($model);
        $itemCodeToBeRemoved = [
            InsuranceFactory::ARTICLE_ID,
            ShippingFactory::ARTICLE_ID,
        ];
        
        unset($data['type']);
        unset($data['date']);
        unset($data['commit']);
        
        foreach ($data['lines'] as $key => $line) {
            //remove shipping costs (shipping information is not in session on first getTax call)
            if (in_array($line['itemCode'], $itemCodeToBeRemoved)) {
                unset($data['lines'][$key]);
                continue;
            }
        }

        return md5(json_encode($data));
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
     * @param \stdClass | string $data
     * @return \stdClass
     */
    private function generateTaxResultFromResponse($data)
    {
        if (is_string($data) || !is_object($data)) {
            throw new \Exception($data);
        }
        $result = new \stdClass();
        $result->totalTaxable = $data->totalTaxable;
        $result->totalTax = $data->totalTax;
        $result->lines = $data->lines;
        
        return $result;
    }
}
