<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;

/**
 * actory to create CreateTransactionModel from the order
 *
 */
class TransactionModelFactoryFromOrder extends AbstractFactory
{
    protected $order;
    protected $discount = 0;

    /**
     * 
     * @param \Shopware\Models\Order\Order $order
     * @return \Avalara\CreateTransactionModel
     */
    public function build(\Shopware\Models\Order\Order $order)
    {
        $this->order = $order;
        /* @var $customer \Shopware\Models\Customer\Customer */
        $customer = $order->getCustomer();

        $model = new CreateTransactionModel();
        $model->code = $order->getNumber();
        $model->businessIdentificationNo = $customer->getBilling()->getVatId();
        $model->commit = true;
        $model->customerCode = $customer->getId();
        $model->date = date('Y-m-d', time());
        $model->discount = $this->discount;
        $model->type = \Avalara\DocumentType::C_SALESINVOICE;
        $model->currencyCode = $order->getCurrency();
        $model->addresses = $this->getAddressesModel();
        $model->lines = $this->getLineModels();
        $model->companyCode = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::COMPANY_CODE_FIELD)
        ;
        
        $exemptionCode = $customer->getAttribute()->getMoptAvalaraExemptionCode();
        if ($exemptionCode) {
            $model->exemptionNo = $exemptionCode;
        }
        
        return $model;
    }

    /**
     * 
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel()
    {
        /* @var $addressFactory AddressFactory */
        $addressFactory = $this->getAdapter()->getFactory('AddressFactory');

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddressFromOrder($this->order);
        
        return $addressesModel;
    }
    
    /**
     * 
     * @return LineItemModel[]
     */
    protected function getLineModels()
    {
        /* @var $lineFactory Line */
        $lineFactory = $this->getAdapter()->getFactory('LineFactory');
        $lines = [];

        foreach ($this->order->getDetails() as $position) {
            $position = $this->convertOrderDetailToLineData($position);
            if ($this->isDiscount($position['modus'])) {
                if ($this->isDiscountGlobal($position)) {
                    $this->discount -= floatval($position['netprice']);
                } else {
                    $position['id'] = LineFactory::ARTICLEID__VOUCHER;
                    $lines[] = $lineFactory->build($position);
                }
            } else {
                $lines[] = $lineFactory->build($position);
            }
        }

        if ($shipment = $this->getShippingCharges()) {
            $lines[] = $lineFactory->build($shipment);
        }

        return $lines;
    }

    /**
     * get shipment information
     *
     * @return array
     */
    protected function getShippingCharges()
    {
        if ($this->order->getInvoiceShipping()) {
            //create shipping item for compatibility reasons with line data
            $shippingItem = [];
            $shippingItem['id'] = LineFactory::ARTICLEID__SHIPPING;
            $shippingItem['ean'] = '';
            $shippingItem['quantity'] = 1;
            $shippingItem['netprice'] = $this->order->getInvoiceShippingNet();
            $shippingItem['brutprice'] = $this->order->getInvoiceShipping();
            $shippingItem['articlename'] = 'Shipping';
            $shippingItem['articleID'] = 0;
            $shippingItem['dispatchID'] = $this->order->getDispatch()->getId();
            return $shippingItem;
        } else {
            return null;
        }
    }

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

    /*
     * Modus
     * 2 = voucher
     * 3 = special basket discount
     * 4 = discount
     */

    protected function isDiscount($modus)
    {
        if (($modus == 2) || ($modus == 4) || ($modus == 3)) {
            return true;
        }
        return false;
    }

    protected function isDiscountGlobal($position)
    {
        if ($position['modus'] != 2) {
            return true;
        }

        if (!$voucher = Shopware()->Models()->getRepository('\Shopware\Models\Voucher\Voucher')->find($position['articleID'])) {
            return true;
        }

        if (!$voucher->getStrict()) {
            return true;
        }
        return false;
    }

}
