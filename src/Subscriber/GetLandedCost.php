<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Avalara\DocumentType;
use Avalara\CreateTransactionModel;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use LandedCostCalculationAPILib\Models\CalculateRequest;

/**
 * Description of Checkout
 *
 */
class GetLandedCost extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_Frontend_Checkout_Confirm' => 'preCalculateLandedCost',
//            'sOrder::sSaveOrder::before' => 'onBeforeSOrderSaveOrder',
//            'sOrder::sSaveOrder::after' => 'onAfterSOrderSaveOrder',
        ];
    }

    /**
     * Call GetLandedCost service
     * @param \Enlight_Event_EventArgs $args
     */
    public function preCalculateLandedCost(\Enlight_Event_EventArgs $args)
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
        $adapter = $this->getAdapter();

        /* @var $model \LandedCostCalculationAPILib\Models\CalculateRequest */
        $model = $adapter->getFactory('LandedCostCalculateRequestFactory')->build();

        if (!$this->isGetLandedCostCall($model) || empty($args->getSubject()->View()->sUserLoggedIn)) {
            $adapter->getLogger()->info('GetLandedCost call for current basket already done / not enabled.');
            
            $this->assignLandedCost($args);
            return;
        }
        
        try {
            $adapter->getLogger()->info('GetLandedCost for current basket.');

            /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetLandedCost */
            $service = $adapter->getService('GetLandedCost');
            $response = $service->calculate($model);

            $session->MoptAvalaraGetLandedCostResult = $response;
            $session->MoptAvalaraGetLandedCostRequestHash = $this->getHashFromRequest($model);
            $this->assignLandedCost($args, $response);
        } catch (\LandedCostCalculationAPILib\APIException $e) {
            $adapter->getLogger()->error('GetLandedCost call failed: ' . $e->getMessage());
            $args->getSubject()->forward('checkout', 'index');
            
            return true;
        }
    }
    
    /**
     * Add a landed cost duty to the view
     * @param \Enlight_Event_EventArgs $args
     */
    private function assignLandedCost(\Enlight_Event_EventArgs $args)
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
        if (empty($session->MoptAvalaraGetLandedCostResult)) {
            return;
        }
        
        $response = $session->MoptAvalaraGetLandedCostResult;
        if (FormCreator::INCOTERMS_DDP === $response->incoterms) {
            return;
        }
        if (!isset($response->landedCost->details->duties) || empty($response->landedCost->details->duties->amount)) {
            return;
        }
        $duty = number_format((float)$response->landedCost->details->duties->amount, 2);
        $this->getAdapter()->getLogger()->info('GetLandedCost call returned: ' . $duty);
        
        /* @var $view \Enlight_View_Default */
        $view = $args->getSubject()->View();
        $currency = strtolower(Shopware()->Shop()->getCurrency()->getSymbol());

        $view->assign('MoptAvalaraLandedCost', $currency . $duty);
    }

    /**
     * check if GetLandedCost call has to be made
     * @param \LandedCostCalculationAPILib\Models\CalculateRequest $model
     * @return boolean
     */
    protected function isGetLandedCostCall($model)
    {
        $landedCostEnabled = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::LANDEDCOST_ENABLED_FIELD)
        ;
        if (!$landedCostEnabled) {
            return false;
        }


        /* @var $session Enlight_Components_Session_Namespace */
        $session = Shopware()->Session();
        
        if (empty($session->sOrderVariables['sDispatch']['id'])) {
            return false;
        }
        
        if (!$session->MoptAvalaraGetLandedCostRequestHash) {
            return true;
        }

        if ($session->MoptAvalaraGetLandedCostRequestHash != $this->getHashFromRequest($model)) {
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
        if (!$getTaxCommitRequest = $this->validateCommitCall()) {
            //@todo error-message
            throw new \Exception('MoptAvalara: invalid call.');
        } elseif (!($getTaxCommitRequest instanceof CreateTransactionModel)) {
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
        $model = $adapter->getFactory('TransactionModelFactory')->build(
            DocumentType::C_SALESINVOICE, 
            true
        );
        
        $adapter->getLogger()->error('validateCommitCall...');
        //prevent parent execution on request mismatch
        if ($session->MoptAvalaraGetTaxRequestHash != $this->getHashFromRequest($model)) {
            $adapter->getLogger()->error('Mismatching requests, do not proceed.');
            return false;
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
            $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')->findOneBy([
                'number' => $orderNumber]);
            $adapter->getLogger()->debug('FoundOrder:', [$order]);
            $sql = "UPDATE s_order_attributes SET " .
                    "mopt_avalara_doc_code = ? " .
                    "WHERE orderID = ?";
            Shopware()->Db()->query($sql, [$result->code, $order->getId()]);
            $adapter->getLogger()->info('Save DocCode: ' . $result->code);
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
     * @param \Avalara\CreateTransactionModel $model
     * @return mixed
     */
    protected function commitTransaction(CreateTransactionModel $model)
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
    protected function getHashFromRequest($model)
    {
        $data = $this->objectToArray($model);

        $this->getAdapter()->getLogger()->debug(json_encode($data));
        return md5(json_encode($data));
    }
}
