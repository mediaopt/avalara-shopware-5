<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Shopware\Models\Order\Order;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;

/**
 * actory to create CreateTransactionModel from the order
 *
 */
class TransactionModelFactoryFromOrder extends AbstractFactory
{
    /**
     * 
     * @param \Shopware\Models\Order\Order $order
     * @return \Avalara\CreateTransactionModel
     */
    public function build(Order $order)
    {
        /* @var $customer \Shopware\Models\Customer\Customer */
        $customer = $order->getCustomer();

        $model = new CreateTransactionModel();
        $model->code = $order->getNumber();
        $model->commit = true;
        $model->customerCode = $customer->getId();
        $model->date = date('Y-m-d', time());
        $model->lines = $this->getLineModels($order);
        $model->discount = $this->getDiscount($order);
        $model->type = \Avalara\DocumentType::C_SALESINVOICE;
        $model->currencyCode = $order->getCurrency();
        $model->addresses = $this->getAddressesModel($order);
        $model->companyCode = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::COMPANY_CODE_FIELD)
        ;
        
        if ($customer->getBilling() && $customer->getBilling()->getVatId()) {
            $model->businessIdentificationNo = $customer->getBilling()->getVatId();
        }
        
        if ($customer->getAttribute() && $customer->getAttribute()->getMoptAvalaraExemptionCode()) {
            $model->exemptionNo = $customer->getAttribute()->getMoptAvalaraExemptionCode();
        }
        
        return $model;
    }

    /**
     * @param \Shopware\Models\Order\Order $order
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel(Order $order)
    {
        /* @var $addressFactory AddressFactory */
        $addressFactory = $this->getAdapter()->getFactory('AddressFactory');

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddressFromOrder($order);
        
        return $addressesModel;
    }
    
    /**
     * @param \Shopware\Models\Order\Order $order
     * @return LineItemModel[]
     */
    protected function getLineModels(Order $order)
    {
        /* @var $lineFactory Line */
        $lineFactory = $this->getAdapter()->getFactory('LineFactory');
        $lines = [];

        foreach ($order->getDetails() as $position) {
            $position = $this->convertOrderDetailToLineData($position);
            if (!LineFactory::isDiscount($position['modus'])) {
                $lines[] = $lineFactory->build($position);
                continue;
            }
            
            if (LineFactory::isNotVoucher($position)) {
                continue;
            }
            
            $position['id'] = LineFactory::ARTICLEID_VOUCHER;
            $lines[] = $lineFactory->build($position);
        }

        if ($shipment = $this->getShippingCharges($order)) {
            $lines[] = $lineFactory->build($shipment);
        }

        return $lines;
    }
    
    /**
     * @param \Shopware\Models\Order\Order $order
     * @return float
     */
    protected function getDiscount(Order $order)
    {
        $discount = 0.0;
        
        foreach ($order->getDetails() as $position) {
            $position = $this->convertOrderDetailToLineData($position);
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
     * get shipment information
     * @param \Shopware\Models\Order\Order $order
     * @return array
     */
    protected function getShippingCharges(Order $order)
    {
        if (!$order->getInvoiceShipping()) {
            return null;
        }
        
        //create shipping item for compatibility reasons with line data
        $shippingItem = [];
        $shippingItem['id'] = LineFactory::ARTICLEID_SHIPPING;
        $shippingItem['ean'] = '';
        $shippingItem['quantity'] = 1;
        $shippingItem['netprice'] = $order->getInvoiceShippingNet();
        $shippingItem['brutprice'] = $order->getInvoiceShipping();
        $shippingItem['articlename'] = 'Shipping';
        $shippingItem['articleID'] = 0;
        $shippingItem['dispatchID'] = $order->getDispatch()->getId();
        
        return $shippingItem;
    }

    /**
     * 
     * @param \Shopware\Models\Order\Detail $detail
     * @return array
     */
    protected function convertOrderDetailToLineData(\Shopware\Models\Order\Detail $detail)
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
