<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Avalara\DocumentType;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\Line;
use Shopware\Plugins\MoptAvalara\Model\GetTaxRequest;

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
        $adapter = $this->getAdapter();
        
        /* @var $getTaxRequest \Shopware\Plugins\MoptAvalara\Adapter\Factory\GetTaxRequest */
        $getTaxRequest = $adapter->getFactory('GetTaxRequest')->build(
            DocumentType::C_SALESORDER,
            false
        );

        if (!$this->isGetTaxCall($getTaxRequest) || empty($args->getSubject()->View()->sUserLoggedIn)) {
            $adapter->getLogger()->info('GetTax call for current basket already done / not enabled.');
            
            return;
        }

        try {
            $adapter->getLogger()->info('GetTax for current basket.');

            /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
            $service = $adapter->getService('GetTax');
            $response = $service->calculate($getTaxRequest);

            $session->MoptAvalaraGetTaxResult = $response;
            $session->MoptAvalaraGetTaxRequestHash = $this->getHashFromRequest($getTaxRequest);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $adapter->getLogger()->error('GetTax call failed: ' . $e->getMessage());
            $args->getSubject()->forward('checkout', 'index');
            
            return true;
        }
    }

    /**
     * check if getTax call has to be made
     * @return boolean
     * @todo: check country (?)
     */
    protected function isGetTaxCall(GetTaxRequest $getTaxRequest)
    {
        $taxEnabled = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::TAX_ENABLED_FIELD)
        ;
        if (!$taxEnabled) {
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
        $adapter = $this->getAdapter();
        $taxRate = $this->getTaxRateForOrderBasketId($args->get('id'), $session->MoptAvalaraGetTaxResult);
        if ($taxRate === null) {
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
        $newPrice['tax'] = $this->getTaxForOrderBasketId($args->get('id'), $session->MoptAvalaraGetTaxResult);

        return $newPrice;
    }

    /**
     * get tax ammount from avalara response
     * @param type $id
     * @param type $taxInformation
     * @return float
     */
    protected function getTaxForOrderBasketId($id, $taxInformation)
    {
        if (!$taxLineInformation = $this->getTaxLineForOrderBasketId($id, $taxInformation)) {
            return 0;
        }

        return (float)$taxLineInformation->tax;
    }
    
    /**
     * get tax rate from avalara response
     * @param type $id
     * @param type $taxInformation
     * @return float
     */
    protected function getTaxRateForOrderBasketId($id, $taxInformation)
    {
        if (!$taxLineInformation = $this->getTaxLineForOrderBasketId($id, $taxInformation)) {
            return 0;
        }

        return ((float)$taxLineInformation->tax / (float)$taxLineInformation->taxableAmount) * 100;
    }

    /**
     * get tax line info from avalara response
     * @param type $id
     * @param type $taxInformation
     * @return \stdClass
     */
    private function getTaxLineForOrderBasketId($id, $taxInformation)
    {
        foreach ($taxInformation->lines as $taxLineInformation) {
            if ($id == $taxLineInformation->itemCode && $taxLineInformation->tax) {
                return $taxLineInformation;
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
        } elseif (!$getTaxCommitRequest instanceof GetTaxRequest) {
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
        $adapter = $this->getAdapter();

        //proceed if no sales order call was made
        if (!$session->MoptAvalaraGetTaxRequestHash) {
            $adapter->getLogger()->info('No sales order call was made.');
            return true;
        }

        /* @var $getTaxCommitRequest GetTaxRequest */
        $getTaxCommitRequest = $adapter->getFactory('GetTaxRequest')->build(
            DocumentType::C_SALESINVOICE, 
            true
        );
        
        //prevent parent execution on request mismatch
        if ($session->MoptAvalaraGetTaxRequestHash != $this->getHashFromRequest($getTaxCommitRequest)) {
            $adapter->getLogger()->error('Mismatching requests, do not proceed.');
            
            return false;
        }

        $adapter->getLogger()->debug('Matching requests, proceed...', array($getTaxCommitRequest));
        
        return $getTaxCommitRequest;
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

        /*@var $getTaxRequest GetTaxRequest */
        if (!$getTaxRequest = Shopware()->Session()->MoptAvalaraGetTaxCommitRequest) {
            $adapter->getLogger()->debug('MoptAvalaragetTaxCommitrequest is empty');
            return;
        }

        $adapter->getLogger()->info('setDocCode');
        $getTaxRequest->setDocCode($orderNumber);
        if ($result = $this->commitTransaction($getTaxRequest)) {
            //update order attributes
            $adapter->getLogger()->debug('UpdateOrderAttributes');
            $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')->findOneBy(array(
                'number' => $orderNumber));
            $adapter->getLogger()->debug('FoundOrder:', array($order));
            $sql = "UPDATE s_order_attributes SET " .
                    "mopt_avalara_doc_code = ? " .
                    "WHERE orderID = ?";
            Shopware()->Db()->query($sql, array($result->code, $order->getId()));
            $adapter->getLogger()->info('Save DocCode:');
        } else {
            $adapter->getLogger()->debug('No Result (Else)');
            //@todo: mark order as uncommitted (?)
        }
        $adapter->getLogger()->debug('End of AfterSaveMethod');
        unset(Shopware()->Session()->MoptAvalaraGetTaxRequestHash);
        unset(Shopware()->Session()->MoptAvalaraGetTaxResult);
    }

    /**
     * commit transaction
     * @return mixed
     */
    protected function commitTransaction(GetTaxRequest $getTaxRequest)
    {
        $adapter = $this->getAdapter();
        $docCommitEnabled = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::DOC_COMMIT_ENABLED_FIELD)
        ;
        if (!$docCommitEnabled) {
            $adapter->getLogger()->info('doc commit is not enabled.');
            
            return false;
        }

        try {
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
            $service = $adapter->getService('GetTax');
            $response = $service->calculate($getTaxRequest);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $adapter->getLogger()->error('GetTax commit call failed.');
            
            return false;
        }
        return $response;
    }

    /**
     * get hash from request to compare calculate & commit call
     * unset changing fields during both calls
     * 
     * @param GetTaxRequest $getTaxRequest
     * @return string
     */
    protected function getHashFromRequest(GetTaxRequest $getTaxRequest)
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

        $taxRate = $this->getTaxRateForOrderBasketId(Line::ARTICLEID__SHIPPING, $session->MoptAvalaraGetTaxResult);
        
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

        if (!$session->MoptAvalaraGetTaxResult->totalTaxable) {
            return;
        }
        //abfangen voucher mode==2 strict=1 => eigene TaxRate zuweisen aus Avalara Response
        $taxRate = ((float)$session->MoptAvalaraGetTaxResult->totalTax / (float)$session->MoptAvalaraGetTaxResult->totalTaxable) * 100;
        
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
        $taxRate = $this->getTaxRateForOrderBasketId(Line::ARTICLEID__VOUCHER, $session->MoptAvalaraGetTaxResult);
        
        $config = Shopware()->Config();
        $config['sVOUCHERTAX'] = $taxRate;
    }

}
