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
        
        if (!$this->cancelTax($order, \Avalara\VoidReasonCode::C_DOCVOIDED)) {
            return;
        }
        
        $this->View()->assign([
            'success' => true,
            'message' => 'Avalara: order has been cancelled successfully.']
        );
    }
    
    /**
     * Will void, delete and recreate a transaction for an order
     * @return void
     */
    public function updateOrderAction()
    {
        if (!$order = $this->getAvalaraOrder()) {
            return;
        }
        
        if (!$this->cancelTax($order, \Avalara\VoidReasonCode::C_DOCVOIDED)) {
            return;
        }
        
        if (!$this->cancelTax($order, \Avalara\VoidReasonCode::C_DOCDELETED)) {
            return;
        }
        
        if (!$this->updateOrder($order)) {
            return;
        }
        
        $this->resetUpdateFlag($order);
        
        $this->View()->assign([
            'success' => true,
            'message' => 'Avalara: order has been updated.']
        );
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

        $this->resetUpdateFlag($order);

        $this->View()->assign([
            'success' => true,
            'message' => 'Avalara: order has been unflagged.']
        );
    }
    
    /**
     * 
     * @return \Shopware\Models\Order\Order
     */
    protected function getAvalaraOrder()
    {
        $repository = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order');
        $order = $repository->find($this->Request()->getParam('id'));
        if (!$order) {
            $this->View()->assign([
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => 'Avalara: invalid order.']
            );
            return null;
        }

        if (!$order->getAttribute()->getMoptAvalaraDocCode()) {
            $this->View()->assign([
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => 'Avalara: order is not registered with Avalara.']
            );
            return null;
        }
        
        return $order;
    }
    
    /**
     * 
     * @param \Shopware\Models\Order\Order $order
     * @param string $cancelCode
     * @return boolean
     */
    protected function cancelTax(\Shopware\Models\Order\Order $order, $cancelCode) 
    {
        $docCode = $order->getAttribute()->getMoptAvalaraDocCode();
        $adapter = $this->getAvalaraSDKAdapter();

        try {
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\CancelTax */
            $service = $adapter->getService('CancelTax');
            $service->cancel($docCode, $cancelCode);
        } catch (\Exception $e) {
            $adapter->getLogger()->error('CancelTax call failed.');
            $this->View()->assign([
                'success' => false,
                'message' => 'Avalara: Cancel Tax call failed.']
            );
            return false;
        }
        
        return true;
    }
    
    /**
     * 
     * @param \Shopware\Models\Order\Order $order
     * @return boolean
     */
    protected function updateOrder(\Shopware\Models\Order\Order $order)
    {
        $adapter = $this->getAvalaraSDKAdapter();
        
        try {
            $getTaxFromOrderRequest = $adapter->getFactory('TransactionModelFactoryFromOrder')->build($order);
            $response = $adapter->getService('GetTax')->calculate($getTaxFromOrderRequest);
            
            /*@var $detail Shopware\Models\Order\Detail */
            foreach ($order->getDetails() as $detail) {
                $taxRate = $this->getTaxRateForOrderDetail($detail, $response);
                $detail->setTax(null);
                $detail->setTaxRate($taxRate);
            }
            Shopware()->Models()->persist($order);
            Shopware()->Models()->flush();
            $order->calculateInvoiceAmount();
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $adapter->getLogger()->error('GetTax call from order failed.');
            $this->View()->assign([
                'success' => false,
                'message' => 'Avalara: Update order call failed.']
            );
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param Shopware\Models\Order\Detail $detail
     * @param \stdClass $getTaxResponse
     * @return int
     * @throws \Exception
     */
    protected function getTaxRateForOrderDetail(Shopware\Models\Order\Detail $detail, $getTaxResponse)
    {
        foreach ($getTaxResponse['TaxLines'] as $taxLineInformation) {
            if ($detail->getId() == $taxLineInformation['LineNo']) {
                //exemption ?
                if (!$taxLineInformation['Tax']) {
                    return 0;
                }
                return ((float)$taxLineInformation['Tax'] / (float)$taxLineInformation['Taxable']) * 100;
            }
        }
        throw new \Exception('Avalara: no tax information found.');
    }
    
    /**
     * 
     * @param \Shopware\Models\Order\Order $order
     */
    protected function resetUpdateFlag(\Shopware\Models\Order\Order $order)
    {
        $order->getAttribute()->setMoptAvalaraOrderChanged(0);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }

    /**
     * 
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected function getAvalaraSDKAdapter() {
        $service = \Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter::SERVICE_NAME;
        return Shopware()->Container()->get($service);
    }
}
