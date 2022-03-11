<?php

namespace Shopware\Plugins\MoptAvalara\Cronjob;

use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;

class OrderCommitCronjob
{
    private $container;

    /**
     * listener-method for cronjob-subscriber
     *
     * @param Shopware_Components_Cron_CronJob $job
     */
    public function onCronjobExecute(\Shopware_Components_Cron_CronJob $job)
    {
        $adapter = $this->getAdapter();
        $service = $adapter->getService('CommitTax');

        $orderIds = $this->getOrderIds();
        foreach($orderIds as $orderId) {
            if (!$order = $adapter->getOrderById($orderId)) {
                continue;
            }
            $service->commitOrder($order);
            $order->getAttribute()->setMoptAvalaraOrderChanged(0);
            Shopware()->Models()->persist($order);
            Shopware()->Models()->flush();
        }

        return true;
    }

    /**
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected function getAdapter()
    {
        if (null === $this->adapter) {
            $this->adapter = $this->getContainer()->get(AvalaraSDKAdapter::SERVICE_NAME);
        }

        return $this->adapter;
    }

    private function getOrderIds()
    {
        $sql = "SELECT o.id
                FROM s_order as o
                INNER JOIN s_order_attributes as oa
                ON o.id = oa.orderID
                WHERE
                o.status = 2 AND oa.mopt_avalara_doc_code IS NULL AND oa.mopt_avalara_transaction_type IS NOT NULL";

        return $this
            ->getContainer()
            ->get('db')
            ->fetchAll($sql)
            ;
    }

    protected function getContainer()
    {
        if (null === $this->container) {
            $this->container = Shopware()->Container();
        }

        return $this->container;
    }
}
