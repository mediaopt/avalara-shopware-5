<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Avalara\DocumentType;
use Shopware\Models\Order\Order;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;

/**
 * Factory to create CreateTransactionModel from the order
 * Will return a model ready to be commited to Avalara
 *
 */
class InvoiceTransactionModelFactory extends AbstractTransactionModelFactory
{
    /**
     *
     * @var Order
     */
    private $orderContext;
    
    /**
     *
     * @param \Shopware\Models\Order\Order $order
     * @return \Avalara\CreateTransactionModel
     */
    public function build(Order $order)
    {
        /* @var $customer \Shopware\Models\Customer\Customer */
        $customer = $order->getCustomer();
        $this->orderContext = $order;
        
        $model = new CreateTransactionModel();
        $model->code = $order->getNumber();
        $model->commit = true;
        $model->customerCode = $customer->getId();
        $model->date = date(DATE_W3C);
        $model->lines = $this->getLineModels();
        $model->discount = $this->getDiscount();
        $model->type = DocumentType::C_SALESINVOICE;
        $model->currencyCode = $order->getCurrency();
        $model->addresses = $this->getAddressesModel();
        $model->companyCode = $this->getCompanyCode();
        
        if ($customer->getBilling() && $customer->getBilling()->getVatId()) {
            $model->businessIdentificationNo = $customer->getBilling()->getVatId();
        }
        
        if ($customer->getAttribute() && $customer->getAttribute()->getMoptAvalaraExemptionCode()) {
            $model->exemptionNo = $customer->getAttribute()->getMoptAvalaraExemptionCode();
        }
        
        return $model;
    }

    /**
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel()
    {
        $addressFactory = $this->getAddressFactory();

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddressFromOrder($this->orderContext);
        
        return $addressesModel;
    }
    
    /**
     * @return LineItemModel[]
     */
    protected function getLineModels()
    {
        $positions = $this->getPositionsFromOrder($this->orderContext);

        return parent::getLineModels($positions);
    }
    
    /**
     * Discount amount from a voucher is a negative value!
     * @return float
     */
    protected function getDiscount()
    {
        $discount = 0.0;
        
        foreach ($this->getPositionsFromOrder($this->orderContext) as $position) {
            if (!LineFactory::isDiscount($position['modus'])) {
                continue;
            }
            
            if (LineFactory::isNotVoucher($position)) {
                $discount -= floatval($position['netprice']);
            }
        }

        return $discount;
    }
    
    /**
     *
     * @return int
     */
    protected function getShippingId()
    {
        return $this->orderContext->getDispatch()->getId();
    }
    
    /**
     *
     * @return float
     */
    protected function getShippingPrice()
    {
        return $this->orderContext->getInvoiceShippingNet();
    }
    
    /**
     * @param \Shopware\Models\Order\Order $order
     * @return array
     */
    private function getPositionsFromOrder(Order $order)
    {
        $positions = [];

        foreach ($order->getDetails() as $position) {
            $positions[] = $this->convertOrderDetailToLineData($position);
        }
        
        return $positions;
    }

    /**
     *
     * @param \Shopware\Models\Order\Detail $detail
     * @return array
     */
    private function convertOrderDetailToLineData(\Shopware\Models\Order\Detail $detail)
    {
        $lineData = [];
        $lineData['id'] = $detail->getId();
        $lineData['ean'] = $detail->getEan();
        $lineData['quantity'] = $detail->getQuantity();
        $lineData['netprice'] = $detail->getPrice();
        $lineData['articlename'] = $detail->getArticleName();
        $lineData['articleID'] = $detail->getArticleId();
        $lineData['modus'] = $detail->getMode();
        
        return $lineData;
    }
}
