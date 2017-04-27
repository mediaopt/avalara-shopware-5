<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

/**
 * Description of Checkout
 *
 */
class GetTax extends AbstractSubscriber
{

    /**
     * return array with all subsribed events
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Shopware_Modules_Basket_getPriceForUpdateArticle_FilterPrice' => 'onGetPriceForUpdateArticle',
            'sArticles::getTaxRateByConditions::after' => 'afterGetTaxRateByConditions',
            'Enlight_Controller_Action_Frontend_Checkout_Confirm' => 'onBeforeCheckoutConfirm',
            'sOrder::sSaveOrder::before' => 'onBeforeSOrderSaveOrder',
            'sOrder::sSaveOrder::after' => 'onAfterSOrderSaveOrder',
            'sAdmin::sGetPremiumDispatch::after' => 'onAfterAdminGetPremiumDispatch',
            'sBasket::sGetBasket::before' => 'onBeforeSBasketSGetBasket',
            'sBasket::sAddVoucher::before' => 'onBeforeBasketSAddVoucher',
        );
    }

    /**
     * call getTax service
     * @param \Enlight_Event_EventArgs $args
     */
    public function onBeforeCheckoutConfirm(\Enlight_Event_EventArgs $args)
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();

        /* @var $sdkMain \Mediaopt\Avalara\Sdk\Main */
        $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');

        /* @var $getTaxRequest \Mediaopt\Avalara\Sdk\Model\GetTaxRequest */
        $getTaxRequest = $sdkMain->getAdapter()->getFactory('GetTaxRequest')->build(
                \Mediaopt\Avalara\Sdk\Model\DocumentType::SALES_ORDER, false);

        if (!$this->isGetTaxCall($getTaxRequest) || empty($args->getSubject()->View()->sUserLoggedIn)) {
            $sdkMain->getLogger()->info('GetTax call for current basket already done / not enabled.');
            return;
        }

        try {
            $sdkMain->getLogger()->info('GetTax for current basket.');

            /* @var $service \Mediaopt\Avalara\Sdk\Service\GetTax */
            $service = $sdkMain->getService('GetTax');
            $session->MoptAvalaraGetTaxResult = $response = $service->call($getTaxRequest);
            $session->MoptAvalaraGetTaxRequestHash = $this->getHashFromRequest($getTaxRequest);
            #$session->MoptAvalaraGetTaxRequestDev = $getTaxRequest;
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $sdkMain->getLogger()->error('GetTax call failed.');
            $args->getSubject()->forward('checkout', 'index');
            return true;
        }
    }

    /**
     * check if getTax call has to be made
     * @return boolean
     * @todo: check country (?)
     */
    protected function isGetTaxCall(\Mediaopt\Avalara\Sdk\Model\GetTaxRequest $getTaxRequest)
    {
        $pluginConfig = Shopware()->Plugins()->Backend()->MoptAvalara()->Config();
        if (!$pluginConfig->mopt_avalara__tax_enabled) {
            return false;
        }


        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();

        if (!$session->MoptAvalaraGetTaxRequestHash) {
            return true;
        }

        if ($session->MoptAvalaraGetTaxRequestHash != $this->getHashFromRequest($getTaxRequest)) {
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
        
        $taxRate = $this->getTaxForOrderBasketId($args->get('id'), $session->MoptAvalaraGetTaxResult);
        if ($taxRate === null) {
            //tax has to be present for all positions on checkout confirm
            if (false && Shopware()->Front()->Request()->getControllerName() == 'checkout' &&
                    Shopware()->Front()->Request()->getActionName() == 'confirm') {
                /* @var $sdkMain \Mediaopt\Avalara\Sdk\Main */
                $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');
                $errorMsg = 'No tax information for basket-position ' . $args->get('id');
                $sdkMain->getLogger()->error($errorMsg);
                throw new \Exception($errorMsg);
            }

            //proceed with shopware standard
            return;
        }

        $newPrice = $args->getReturn();
        $newPrice['taxID'] = 'mopt_avalara__' . $taxRate;
        $newPrice['tax_rate'] = $taxRate;
        $newPrice['tax'] = $taxRate;
        return $newPrice;
    }

    /**
     * get tax rate from avalara response
     * @param type $id
     * @param type $taxInformation
     * @return float
     */
    protected function getTaxForOrderBasketId($id, $taxInformation)
    {
        foreach ($taxInformation['TaxLines'] as $taxLineInformation) {
            if ($id == $taxLineInformation['LineNo']) {
                //exemption ?
                if (!$taxLineInformation['Tax']) {
                    return 0;
                }
                return ((float)$taxLineInformation['Tax'] / (float)$taxLineInformation['Taxable']) * 100;
                #return (float) $taxLineInformation['Rate'] * 100;
            }
        }

        return null;
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
        if (!$getTaxCommitRequest = $this->validateCommitCall()) {
            //@todo error-message
            throw new \Exception('MoptAvalara: invalid call.');
        } elseif (!$getTaxCommitRequest instanceof \Mediaopt\Avalara\Sdk\Model\GetTaxRequest) {
            //not in avalara context
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
     * @return boolean
     */
    protected function validateCommitCall()
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
        /* @var $sdkMain \Mediaopt\Avalara\Sdk\Main */
        $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');

        //proceed if no sales order call was made
        if (!$session->MoptAvalaraGetTaxRequestHash) {
            $sdkMain->getLogger()->info('No sales order call was made.');
            return true;
        }

        /* @var $getTaxCommitRequest \Mediaopt\Avalara\Sdk\Model\GetTaxRequest */
        $getTaxCommitRequest = $sdkMain->getAdapter()->getFactory('GetTaxRequest')->build(
                \Mediaopt\Avalara\Sdk\Model\DocumentType::SALES_INVOICE, true);
        //prevent parent execution on request mismatch
        if ($session->MoptAvalaraGetTaxRequestHash != $this->getHashFromRequest($getTaxCommitRequest)) {
            $sdkMain->getLogger()->error('Mismatching requests, do not proceed.');
            return false;
        }

        $sdkMain->getLogger()->debug('Matching requests, proceed...', array($getTaxCommitRequest));
        return $getTaxCommitRequest;
    }

    /**
     * commit transaction
     * @param \Enlight_Event_EventArgs $args
     * @return type
     */
    public function onAfterSOrderSaveOrder(\Enlight_Hook_HookArgs $args)
    {
        $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');
        $sdkMain->getLogger()->info('onAfterSOrderSaveOrder call');
        if (!$orderNumber = $args->getReturn()) {
            $sdkMain->getLogger()->debug('orderNumber did not exist');
            return;
        }

        /*@var $getTaxRequest \Mediaopt\Avalara\Sdk\Model\GetTaxRequest */
        if (!$getTaxRequest = Shopware()->Session()->MoptAvalaraGetTaxCommitRequest) {
            $sdkMain->getLogger()->debug('MoptAvalaragetTaxCommitrequest is empty');
            return;
        }

        $sdkMain->getLogger()->info('setDocCode');
        $getTaxRequest->setDocCode($orderNumber);
        if ($result = $this->commitTransaction($getTaxRequest)) {
            //update order attributes
            $sdkMain->getLogger()->debug('UpdateOrderAttributes');
            $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')->findOneBy(array(
                'number' => $orderNumber));
            $sdkMain->getLogger()->debug('FoundOrder:', array($order));
            $sql = "UPDATE s_order_attributes SET " .
                    "mopt_avalara_doc_code = ? " .
                    "WHERE orderID = ?";
            Shopware()->Db()->query($sql, array($result['DocCode'], $order->getId()));
            $sdkMain->getLogger()->info('Save DocCode:');
        } else {
            $sdkMain->getLogger()->debug('No Result (Else)');
            //@todo: mark order as uncommitted (?)
        }
        $sdkMain->getLogger()->debug('End of AfterSaveMethod');
        unset(Shopware()->Session()->MoptAvalaraGetTaxRequestHash);
        unset(Shopware()->Session()->MoptAvalaraGetTaxResult);
    }

    /**
     * commit transaction
     * @return mixed
     */
    protected function commitTransaction(\Mediaopt\Avalara\Sdk\Model\GetTaxRequest $getTaxRequest)
    {
        /* @var $sdkMain \Mediaopt\Avalara\Sdk\Main */
        $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');
        $pluginConfig = Shopware()->Plugins()->Backend()->MoptAvalara()->Config();

        if (!$pluginConfig->mopt_avalara__doc_commit_enabled) {
            $sdkMain->getLogger()->info('doc commit is not enabled.');
            return false;
        }

        try {
            /* @var $service \Mediaopt\Avalara\Sdk\Service\GetTax */
            $service = $sdkMain->getService('GetTax');
            $response = $service->call($getTaxRequest);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $sdkMain->getLogger()->error('GetTax commit call failed.');
            return false;
        }
        return $response;
    }

    /**
     * get hash from request to compare calculate & commit call
     * unset changing fields during both calls
     * 
     * @param \Mediaopt\Avalara\Sdk\Model\GetTaxRequest $getTaxRequest
     * @return string
     */
    protected function getHashFromRequest(\Mediaopt\Avalara\Sdk\Model\GetTaxRequest $getTaxRequest)
    {
        $data = $getTaxRequest->toArray();
        unset($data['DocType']);
        unset($data['Commit']);

        foreach ($data['Lines'] as $key => $line) {
            unset($data['Lines'][$key]['LineNo']);
            unset($data['Lines'][$key]['Amount']);

            //remove shipping costs (shipping information is not in session on first getTax call)
            if ($line['LineNo'] == 'shipping') {
                unset($data['Lines'][$key]);
            }
        }
        return md5(json_encode($data));
    }

    public function onAfterAdminGetPremiumDispatch(\Enlight_Hook_HookArgs $args)
    {
        $return = $args->getReturn();
        
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();

        if (empty($session->MoptAvalaraGetTaxResult)) {
            return;
        }

        $taxRate = $this->getTaxForOrderBasketId(\Mediaopt\Avalara\Adapter\Shopware4\Factory\Line::ARTICLEID__SHIPPING, 
                $session->MoptAvalaraGetTaxResult);
        
        if(!$taxRate) {
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

        if (!$session->MoptAvalaraGetTaxResult['TotalTaxable']) {
            return;
        }
        //abfangen voucher mode==2 strict=1 => eigene TaxRate zuweisen aus Avalara Response
        $taxRate = ((float)$session->MoptAvalaraGetTaxResult['TotalTax'] / (float)$session->MoptAvalaraGetTaxResult['TotalTaxable']) * 100;
        
        $config = Shopware()->Config();
        $config['sDISCOUNTTAX'] = number_format($taxRate, 2);
        $config['sTAXAUTOMODE'] = false;
    }
    
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
              )', array($voucherCode)
                ) ? : array();

        if (empty($voucherDetails['strict'])) {
            return;
        }
        
        //get tax rate for voucher
        $taxRate = $this->getTaxForOrderBasketId(\Mediaopt\Avalara\Adapter\Shopware4\Factory\Line::ARTICLEID__VOUCHER, 
                $session->MoptAvalaraGetTaxResult);
        
        $config = Shopware()->Config();
        $config['sVOUCHERTAX'] = $taxRate;
    }

}
