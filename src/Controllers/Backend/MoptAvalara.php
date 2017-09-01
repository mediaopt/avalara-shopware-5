<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

/**
 * @extends Shopware_Controllers_Backend_ExtJs
 * @author derksen mediaopt GmbH
 */
class Shopware_Controllers_Backend_MoptAvalara extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Will cancel a transaction for an order
     * @return void
     */
    public function cancelOrderAction()
    {
        try {
            $id = $this->Request()->getParam('id');
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\CancelTax */
            $service = $this->getAdapter()->getService('CancelTax');
            $service->cancel($this->getAvalaraOrder($id));
            
            $this->View()->assign([
                'success' => true,
                'message' => 'Avalara: order has been cancelled successfully.'
            ]);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Will commit transaction to Avalara
     * @return void
     */
    public function commitOrderAction()
    {
        try {
            $id = $this->Request()->getParam('id');
            $order = $this->getAvalaraOrder($id);
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\CommitTax */
            $service = $this->getAdapter()->getService('CommitTax');
            $service->commitOrder($order);
            $this->resetUpdateFlag($order);
            
            $this->View()->assign([
                'success' => true,
                'message' => 'Avalara: order has been commited to Avalara.'
            ]);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     *
     * @return void
     */
    public function resetUpdateFlagAction()
    {
        try {
            $id = $this->Request()->getParam('id');
            $order = $this->getAvalaraOrder($id);
            $this->resetUpdateFlag($order);
            
            $this->View()->assign([
                'success' => true,
                'message' => 'Avalara: order has been unflagged.'
            ]);
        } catch (\Exception $e) {
            $this->View()->assign([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param int $id
     * @return \Shopware\Models\Order\Order
     * @throws \RuntimeException
     */
    protected function getAvalaraOrder($id)
    {
        $adapter = $this->getAdapter();
        if (!$order = $adapter->getOrderById($id)) {
            $adapter->getLogger()->error('No order with id: ' . $id);
            throw new \RuntimeException('Avalara: invalid order.');
        }
        
        if (null === $order->getAttribute() || !$transactionType = $order->getAttribute()->getMoptAvalaraTransactionType()) {
            $adapter->getLogger()->error('Avalara: order with id: ' . $id . ' is not registered with Avalara.');
            throw new \RuntimeException('Avalara: order is not registered with Avalara.');
        }
        
        return $order;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     */
    protected function resetUpdateFlag(\Shopware\Models\Order\Order $order)
    {
        $order->getAttribute()->setMoptAvalaraOrderChanged(0);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
        
        $adapter = $this->getAdapter();
        $adapter->getLogger()->info('Order ' . $order->getId() . ' has been unflagged.');
    }

    /**
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected function getAdapter()
    {
        $service = \Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter::SERVICE_NAME;
        return Shopware()->Container()->get($service);
    }
}
