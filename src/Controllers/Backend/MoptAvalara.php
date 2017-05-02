<?php

class Shopware_Controllers_Backend_MoptAvalara extends Shopware_Controllers_Backend_ExtJs
{
    public function cancelOrderAction()
    {
        if (!$order = $this->getAvalaraOrder()) {
            return;
        }
        
        if (!$this->cancelTax($order, Shopware\Plugins\MoptAvalara\Model\CancelCode::DOC_VOIDED)) {
            return;
        }
        
        $this->View()->assign(array(
            'success' => true,
            'message' => 'Avalara: order has been cancelled successfully.')
        );
    }
    
    public function updateOrderAction()
    {
        if (!$order = $this->getAvalaraOrder()) {
            return;
        }
        
        if (!$this->cancelTax($order, Shopware\Plugins\MoptAvalara\Model\CancelCode::DOC_VOIDED)) {
            return;
        }
        
        if (!$this->cancelTax($order, Shopware\Plugins\MoptAvalara\Model\CancelCode::DOC_DELETED)) {
            return;
        }
        
        if (!$this->updateOrder($order)) {
            return;
        }
        
        $this->resetUpdateFlag($order);
        
        $this->View()->assign(array(
            'success' => true,
            'message' => 'Avalara: order has been updated.')
        );
    }
    
    protected function getAvalaraOrder()
    {
        $order = Shopware()->Models()->getRepository('\Shopware\Models\Order\Order')->find(
                $this->Request()->getParam('id'));
        if (!$order) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => 'Avalara: invalid order.')
            );
            return null;
        }

        if (!$order->getAttribute()->getMoptAvalaraDocCode()) {
            $this->View()->assign(array(
                'success' => false,
                'data' => $this->Request()->getParams(),
                'message' => 'Avalara: order is not registered with Avalara.')
            );
            return null;
        }
        
        return $order;
    }
    
    protected function cancelTax(\Shopware\Models\Order\Order $order, $cancelCode) 
    {
        $docCode = $order->getAttribute()->getMoptAvalaraDocCode();

        /* @var $sdkMain \Shopware\Plugins\MoptAvalara\Main */
        $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');

        try {
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
            $service = $sdkMain->getService('CancelTax');
            $service->call($docCode, $cancelCode);
        } catch (\GuzzleHttp\Exception\TransferException $e) {
            $sdkMain->getLogger()->error('CancelTax call failed.');
            $this->View()->assign(array(
                'success' => false,
                'message' => 'Avalara: Cancel Tax call failed.')
            );
            return false;
        }
        
        return true;
    }
    
    protected function updateOrder(\Shopware\Models\Order\Order $order)
    {
        //get tax call on current order state
        /* @var $sdkMain \Shopware\Plugins\MoptAvalara\Main */
        $sdkMain = Shopware()->Container()->get('MediaoptAvalaraSdkMain');
        
        try {
            $getTaxFromOrderRequest = $sdkMain->getAdapter()->getFactory('GetTaxRequestFromOrder')->build($order);
            $response = $sdkMain->getService('GetTax')->call($getTaxFromOrderRequest);
            
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
            $sdkMain->getLogger()->error('GetTax call from order failed.');
            $this->View()->assign(array(
                'success' => false,
                'message' => 'Avalara: Update order call failed.')
            );
            return false;
        }
        return true;
    }
    
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
        throw new Exception('Avalara: no tax information found.');
    }
    
    protected function resetUpdateFlag(\Shopware\Models\Order\Order $order)
    {
        $order->getAttribute()->setMoptAvalaraOrderChanged(0);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
    
    public function resetUpdateFlagAction()
    {
        if (!$order = $this->getAvalaraOrder()) {
            return;
        }

        $this->resetUpdateFlag($order);

        $this->View()->assign(array(
            'success' => true,
            'message' => 'Avalara: order has been unflagged.')
        );
    }
}
