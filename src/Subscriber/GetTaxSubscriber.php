<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Form\PluginConfigForm;
use Avalara\CreateTransactionModel;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\InsuranceFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;

class GetTaxSubscriber extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Basket_getPriceForUpdateArticle_FilterPrice' => 'onGetPriceForUpdateArticle',
            'sArticles::getTaxRateByConditions::after' => 'afterGetTaxRateByConditions',
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
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
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
            ->getPluginConfig(PluginConfigForm::TAX_ENABLED_FIELD)
        ;
        if (!$taxEnabled) {
            return false;
        }


        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
        if (!$session->MoptAvalaraGetTaxResult || !$session->MoptAvalaraGetTaxRequestHash) {
            return true;
        }

        if ($session->MoptAvalaraGetTaxRequestHash !== $this->getHashFromRequest($model)) {
            return true;
        }

        return false;
    }

    /**
     * calculate prices
     * @param \Enlight_Event_EventArgs $args
     */
    public function onGetPriceForUpdateArticle(\Enlight_Event_EventArgs $args)
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
  
        if (empty($session->MoptAvalaraGetTaxResult)) {
            return;
        }

        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        $taxRate = $service->getTaxRateForOrderBasketId($session->MoptAvalaraGetTaxResult, $args->get('id'));
        
        if (null === $taxRate) {
            //tax has to be present for all positions on checkout confirm
            $action = Shopware()->Front()->Request()->getActionName();
            $controller = Shopware()->Front()->Request()->getControllerName();
            if ('checkout' == $controller && 'confirm' == $action) {
                $msg = 'No tax information for basket-position ' . $args->get('id');
                $adapter->getLogger()->error($msg);
                
                //@todo Check if we should remove this
                //customer should not be warning if avalara is not working.
                //throw new \Exception($msg);
            }

            //proceed with shopware standard
            return;
        }
        
        $newPrice = $args->getReturn();
        $newPrice['taxID'] = 'mopt_avalara__' . $taxRate;
        $newPrice['tax_rate'] = $taxRate;
        $newPrice['tax'] = $service->getTaxForOrderBasketId($session->MoptAvalaraGetTaxResult, $args->get('id'));
        
        return $newPrice;
    }

    /**
     * set tax rate
     * @param \Enlight_Hook_HookArgs $args
     */
    public function afterGetTaxRateByConditions(\Enlight_Hook_HookArgs $args)
    {
        $taxId = $args->get('taxId');
        if (!preg_match('#^mopt_avalara__(.+)$#', $taxId, $matches)) {
            return;
        }

        $args->setReturn($matches[1]);
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
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
        $adapter = $this->getAdapter();

        //proceed if no sales order call was made
        if (!$session->MoptAvalaraGetTaxRequestHash) {
            $adapter->getLogger()->info('No sales order call was made.');
            return true;
        }

        /* @var $model \Avalara\CreateTransactionModel */
        $model = $adapter->getFactory('OrderTransactionModelFactory')->build();
        
        $adapter->getLogger()->error('validateCommitCall...');
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

        $adapter->getLogger()->info('setDocCode: ' . $orderNumber);
        if ($result = $this->commitTransaction($model)) {
            //update order attributes
            $adapter->getLogger()->debug('UpdateOrderAttributes');
            $adapter->getLogger()->info('Save DocCode: ' . $result->code);
            
            $this->updateOrderAttributes($model, $orderNumber, $result);
        } else {
            $adapter->getLogger()->debug('No Result (Else)');
            //@todo: mark order as uncommitted (?)
        }
        $adapter->getLogger()->debug('End of AfterSaveMethod');
        unset(Shopware()->Session()->MoptAvalaraGetTaxRequestHash);
        unset(Shopware()->Session()->MoptAvalaraGetTaxResult);
    }
    
    /**
     *
     * @param CreateTransactionModel $model
     * @param int $orderNumber
     * @param type $result
     */
    private function updateOrderAttributes(CreateTransactionModel $model, $orderNumber, $result)
    {
        $order = Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->findOneBy(['number' => $orderNumber])
        ;
        if (!$order) {
            $msg = 'There is no order with number: ' . $orderNumber;
            $this->getAdapter()->getLogger()->critical($msg);
            throw new \Exception($msg);
        }
        $order->getAttribute()->setMoptAvalaraDocCode($result->code);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }

    /**
     * commit transaction
     * @param \Avalara\CreateTransactionModel $model
     * @return mixed
     */
    protected function commitTransaction(CreateTransactionModel $model)
    {
        $adapter = $this->getAdapter();
        $docCommitEnabled = $this
            ->getAdapter()
            ->getPluginConfig(PluginConfigForm::DOC_COMMIT_ENABLED_FIELD)
        ;
        if (!$docCommitEnabled) {
            $adapter->getLogger()->info('doc commit is not enabled.');
            
            return false;
        }

        try {
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
            $service = $adapter->getService('GetTax');
            $response = $service->calculate($model);
        } catch (\Exception $e) {
            $adapter->getLogger()->error('GetTax commit call failed.');
            
            return false;
        }
        return $response;
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
        
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();

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
        $args->setReturn($return);
    }
    
    /**
     * set taxrate for discounts
     * @param \Enlight_Hook_HookArgs $args
     * @return type
     */
    public function onBeforeSBasketSGetBasket(\Enlight_Hook_HookArgs $args)
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();

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
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();

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
