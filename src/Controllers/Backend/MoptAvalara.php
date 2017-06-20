<?php

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
            $this->commitOrder($order);
            $this->resetUpdateFlag($order);
            
            $this->View()->assign([
                'success' => true,
                'message' => 'Avalara: order has been updated.'
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
     */
    protected function getAvalaraOrder($id)
    {
        $adapter = $this->getAdapter();
        if (!$order = $adapter->getOrderById($id)) {
            $adapter->getLogger()->error('No order with id: ' . $id);
            throw new \Exception('Avalara: invalid order.');
        }

        if (!$order->getAttribute()->getMoptAvalaraTransactionType()) {
            $adapter->getLogger()->error('Avalara: order with id: ' . $id . ' is not registered with Avalara.');
            throw new \Exception('Avalara: order is not registered with Avalara.');
        }
        
        return $order;
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @return boolean
     */
    protected function commitOrder(\Shopware\Models\Order\Order $order)
    {
        $adapter = $this->getAdapter();
        try {
            $docCommitEnabled = $adapter->getPluginConfig(Form::DOC_COMMIT_ENABLED_FIELD);
            if (!$docCommitEnabled) {
                throw new \Exception('Doc commit is not enabled.');
            }
            
            $model = $adapter
                ->getFactory('InvoiceTransactionModelFactory')
                ->build($order)
            ;
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
            $service = $adapter->getService('GetTax');
            if (!$response = $service->calculate($model)) {
                 throw new \Exception('No result on order commiting to Avalara.');
            }
            $adapter->getLogger()->info('Order ' . $order->getId() . ' has been commited with docCode: ' . $response->code);
            $this->updateOrder($order, $response);
        } catch (\Exception $e) {
            $adapter->getLogger()->error('Commiting order to Avalara failed: '. $e->getMessage());
            throw new \Exception('Avalara: Update order call failed: ' . $e->getMessage());
        }
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @return boolean
     */
    protected function resetUpdateFlag(\Shopware\Models\Order\Order $order)
    {
        $order->getAttribute()->setMoptAvalaraOrderChanged(0);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @param \stdClass $response
     * @return boolean
     */
    protected function updateOrder(\Shopware\Models\Order\Order $order, $response)
    {
        $attr = $order->getAttribute();
        $attr->setMoptAvalaraDocCode($response->code);
        $attr->setMoptAvalaraTransactionType(\Avalara\DocumentType::C_SALESINVOICE);
        
        /*@var $detail Shopware\Models\Order\Detail */
        foreach ($order->getDetails() as $detail) {
            $taxRate = $this->getTaxRateForOrderDetail($detail, $response);
            $detail->setTax(null);
            $detail->setTaxRate($taxRate);
        }
        $order->calculateInvoiceAmount();
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Detail $detail
     * @param \stdClass $taxInformation
     * @return float
     * @throws \Exception
     */
    protected function getTaxRateForOrderDetail(\Shopware\Models\Order\Detail $detail, $taxInformation)
    {
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        if ($taxRate = $service->getTaxRateForOrderBasketId($detail->getId(), $taxInformation)) {
            return $taxRate;
        }

        throw new \Exception('Avalara: no tax information found.');
    }
    
    /**
     *
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected function getAdapter()
    {
        $service = \Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter::SERVICE_NAME;
        return Shopware()->Container()->get($service);
    }
}
