<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Bootstrap\Database;

/**
 * Description of OrderSubscriber
 *
 */
class OrderSubscriber extends AbstractSubscriber
{    
    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetOpenOrderData_FilterResult' => 'onFilterOrders',
        ];
    }

    /**
     * Updates totals with Avalara subcharge
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFilterOrders(\Enlight_Event_EventArgs $args)
    {
        $orders = $args->getReturn();
        $avalaraAttributes = $this->getAvalaraAttributes(array_column($orders, 'id'));

        foreach ($orders as $i => $orderData) {
            $id = $orderData['id'];

            $orderAttr = $avalaraAttributes[$id];
            $orders[$i]['moptAvalaraLandedCost'] = (float)$orderAttr[Database::LANDEDCOST_FIELD];
            $orders[$i]['moptAvalaraInsurance'] = (float)$orderAttr[Database::INSURANCE_FIELD];
        }

        return $orders;
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
}
