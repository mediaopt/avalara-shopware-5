<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Models\Order\Order;
use Shopware\Plugins\MoptAvalara\Bootstrap\Database;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Avalara\DocumentType;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;

/**
 * Description of OrderSubscriber
 *
 */
class OrderSubscriber extends AbstractSubscriber
{    
    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetOpenOrderData_FilterResult' => 'onFilterOrders',
            'sOrder::sSaveOrder::before' => 'onBeforeSaveOrder',
            'sOrder::sSaveOrder::after' => 'onAfterSaveOrder',
        ];
    }

    /**
     * Updates totals with Avalara surcharge
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFilterOrders(\Enlight_Event_EventArgs $args)
    {
        $orders = $args->getReturn();
        if (empty($orders)) {
            return $orders;
        }

        $avalaraAttributes = $this->getAvalaraAttributes(array_column($orders, 'id'));

        foreach ($orders as $i => $orderData) {
            $id = $orderData['id'];
            $orderAttr = $avalaraAttributes[$id];
            $orders[$i]['moptAvalaraLandedCost'] = (float)$orderAttr[Database::LANDEDCOST_FIELD];
            $orders[$i]['moptAvalaraInsurance'] = (float)$orderAttr[Database::INSURANCE_FIELD];
            $surcharge = $this
                ->bcMath
                ->bcadd($orders[$i]['moptAvalaraLandedCost'], $orders[$i]['moptAvalaraInsurance'])
            ;
            $orders[$i]['invoice_shipping'] = $this->getShippingWithoutSurcharge($orders[$i]['invoice_shipping'], $surcharge);
        }

        return $orders;
    }

    /**
     * Commit transaction
     *
     * @param \Enlight_Hook_HookArgs $args
     * @throws \RuntimeException
     */
    public function onAfterSaveOrder(\Enlight_Hook_HookArgs $args)
    {
        $adapter = $this->getAdapter();
        $session = $this->getSession();
        $order = $this->validateOnSaveOrder($args);
        $taxRequest = $session->MoptAvalaraGetTaxCommitRequest;
        $taxResult = $session->MoptAvalaraGetTaxResult;
        if (!$taxRequest || !$taxResult) {
            $adapter->getLogger()->debug('No Avalara info for order ' . $args->getReturn());
        }

        $session->MoptAvalaraOnFinishGetTaxResult = $session->MoptAvalaraGetTaxResult;
        $this->setOrderAttributes($order, $taxRequest, $taxResult);

        unset(
            $session->MoptAvalaraGetTaxRequestHash,
            $session->MoptAvalaraGetTaxCommitRequest,
            $session->MoptAvalaraGetTaxResult
        );
    }

    /**
     * Check if current basket matches with previous avalara call (e.g. multi tab)
     *
     * @param \Enlight_Hook_HookArgs $args
     * @throws \RuntimeException
     */
    public function onBeforeSaveOrder(\Enlight_Hook_HookArgs $args)
    {
        $getTaxCommitRequest = $this->validateCommitCall();
        if (!$getTaxCommitRequest) {
            $this
                ->getAdapter()
                ->getLogger()
                ->error('Not in avalara context')
            ;
            return $args->getReturn();
        }
        $this->getSession()->MoptAvalaraGetTaxCommitRequest = $getTaxCommitRequest;

        $args->getSubject()->sBasketData['content'] = $this->resetBasketTaxId($args->getSubject()->sBasketData['content']);

        return $args->getReturn();
    }


    /**
     * @param Order $order
     * @param \stdClass $taxRequest
     * @param \stdClass $taxResult
     */
    private function setOrderAttributes(Order $order, $taxRequest, $taxResult)
    {
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        if (!$service->isGetTaxEnabled()) {
            return;
        }

        if ($service->isGetTaxDisabledForCountry()) {
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

        $subshopId = $this
            ->getAdapter()
            ->getShopContext()
            ->getId()
        ;

        $order->getAttribute()->setMoptAvalaraSubshopId($subshopId);
        $order->getAttribute()->setMoptAvalaraIncoterms($incoterms);
        $order->getAttribute()->setMoptAvalaraLandedcost($landedCost);
        $order->getAttribute()->setMoptAvalaraInsurance($insurance);

        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }

    /**
     * @param array $basketContent
     * @return array
     */
    private function resetBasketTaxId($basketContent = [])
    {
        array_walk(
            $basketContent,
            function (&$basketRow) {
                $basketRow['taxId'] = 0;
                $basketRow['taxID'] = 0;
            }
        );

        return $basketContent;
    }

    /**
     * validate commit call with previous getTax call
     *
     * @return \Avalara\CreateTransactionModel | bool
     * @throws \RuntimeException
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
            $adapter->getLogger()->info('Mismatching requests, basket has been changed by someone.');

            if ($adapter->getPluginConfig(Form::BASKET_CONSISTENCY_CHECK_FIELD)) {
                throw new \RuntimeException('Mismatching requests, basket has been changed by someone. That might be a plugin. If you are sure that does not change price, count or other sensitive data of the basket, disable "Check basket for consistency on checkout completion" in the Avalara plugin config.');
            }
        }

        $adapter->getLogger()->debug('Matching requests, proceed...', [$model]);

        return $model;
    }

    /**
     * @param array $orderIds
     * @return array
     */
    private function getAvalaraAttributes($orderIds)
    {
        $columnsToFetch = [
            'orderID',
            Database::LANDEDCOST_FIELD,
            Database::INSURANCE_FIELD,
        ];

        $sql = 'SELECT ' . implode(', ', $columnsToFetch)
            . ' FROM '. Database::ORDER_ATTR_TABLE
            . ' WHERE orderID IN ('.implode(', ', $orderIds).')'
        ;

        $attrs = $this
            ->getContainer()
            ->get('db')
            ->fetchAll($sql)
        ;
        
        $avalaraAttributes = [];
        foreach ($attrs as $attr) {
            $orderID = $attr['orderID'];
            unset($attr['orderID']);
            $avalaraAttributes[$orderID] = $attr;
        }

        return $avalaraAttributes;
    }

    /**
     * @param \Enlight_Hook_HookArgs $args
     * @return Order
     */
    protected function validateOnSaveOrder(\Enlight_Hook_HookArgs $args)
    {
        $adapter = $this->getAdapter();
        $adapter->getLogger()->debug('onAfterSaveOrder call');

        if (!$orderNumber = $args->getReturn()) {
            $msg = 'orderNumber did not exist';
            $adapter->getLogger()->critical($msg);
            throw new \RuntimeException($msg);
        }

        if (!$order = $adapter->getOrderByNumber($orderNumber)) {
            $msg = 'There is no order with number: ' . $orderNumber;
            $this->getAdapter()->getLogger()->critical($msg);
            throw new \RuntimeException($msg);
        }

        return $order;
    }
}
