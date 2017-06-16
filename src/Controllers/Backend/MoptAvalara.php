<?php

class Shopware_Controllers_Backend_MoptAvalara extends Shopware_Controllers_Backend_ExtJs
{
    /**
     * Will cancel a transaction for an order
     * @return void
     */
    public function cancelOrderAction()
    {
        if (!$order = $this->getAvalaraOrder()) {
            return;
        }
        
        if (!$this->cancelTax($order)) {
            return;
        }
        
        $this->View()->assign([
            'success' => true,
            'message' => 'Avalara: order has been cancelled successfully.'
        ]);
    }
    
    /**
     * Will commit transaction to Avalara
     * @return void
     */
    public function commitOrderAction()
    {
        if (!$order = $this->getAvalaraOrder()) {
            return;
        }

        if (!$this->commitOrder($order)) {
            return;
        }

        $this->resetUpdateFlag($order);
        
        $this->View()->assign([
            'success' => true,
            'message' => 'Avalara: order has been updated.'
        ]);
    }
    
    /**
     *
     * @return void
     */
    public function resetUpdateFlagAction()
    {
        if (!$order = $this->getAvalaraOrder()) {
            return;
        }

        $order->getAttribute()->setMoptAvalaraOrderChanged(0);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();

        $this->View()->assign([
            'success' => true,
            'message' => 'Avalara: order has been unflagged.'
        ]);
    }
    
    /**
     *
     * @return \Shopware\Models\Order\Order
     */
    protected function getAvalaraOrder()
    {
        $order = Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Order\Order')
            ->find($this->Request()->getParam('id'))
        ;
        if (!$order) {
            $this->View()->assign([
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => 'Avalara: invalid order.'
            ]);
            return null;
        }

        if (!$order->getAttribute()->getMoptAvalaraTransactionType()) {
            $this->View()->assign([
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => 'Avalara: order is not registered with Avalara.'
            ]);
            return null;
        }
        
        return $order;
    }
    
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @return boolean
     */
    protected function cancelTax(\Shopware\Models\Order\Order $order, $cancelCode)
    {
        $docCode = $order->getAttribute()->getMoptAvalaraDocCode();
        $adapter = $this->getAdapter();

        try {
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\CancelTax */
            $service = $adapter->getService('CancelTax');
            $service->cancel($docCode);
            
            $order
                ->getAttribute()
                ->setMoptAvalaraTransactionType(\Avalara\VoidReasonCode::C_DOCVOIDED)
            ;
            Shopware()->Models()->persist($order);
            Shopware()->Models()->flush();
            return true;
        } catch (\Exception $e) {
            $adapter->getLogger()->error('CancelTax call failed: ' . $e->getMessage());
            $this->View()->assign([
                'success' => false,
                'message' => 'Avalara: Cancel Tax call failed: ' . $e->getMessage()
            ]);
            return false;
        }
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
                $adapter->getLogger()->info('Doc commit is not enabled.');

                return false;
            }
            
            $model = $adapter
                ->getFactory('InvoiceTransactionModelFactory')
                ->build($order)
            ;
            if (!$response = $adapter->getService('GetTax')->calculate($model)) {
                 $adapter->getLogger()->debug('No result on order commiting to Avalara');
                 return false;
            }
            $adapter->getLogger()->info('Order ' . $order->getId() . ' has been commited with docCode: ' . $response->code);
            $this->updateOrder($order, $response);
            
            return true;
        } catch (\Exception $e) {
            $adapter->getLogger()->error('GetTax call from order failed: '. $e->getMessage());
            $this->View()->assign([
                'success' => false,
                'message' => 'Avalara: Update order call failed: ' . $e->getMessage()
            ]);
            
            return false;
        }
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
