<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Avalara\DocumentType;
use Shopware\Models\Order\Order;
use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;
use Shopware\Models\Order\Detail;

/**
 *
 * Factory to create CreateTransactionModel from the order.
 * Will return a model ready to be commited to Avalara.
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
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
        $model->commit = true;
        $model->customerCode = $customer->getId();
        $model->date = date(DATE_W3C);
        $model->lines = $this->getLineModels();
        $model->discount = $this->getDiscount();
        $model->type = DocumentType::C_SALESINVOICE;
        $model->currencyCode = $order->getCurrency();
        $model->addresses = $this->getAddressesModel();
        $model->companyCode = $this->getCompanyCode();
        $model->parameters = $this->getTransactionParameters();
        
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
     * @param array $positions
     * @return LineItemModel[]
     */
    protected function getLineModels($positions = [])
    {
        $orderPositions = $this->getPositionsFromOrder($this->orderContext);

        return parent::getLineModels($orderPositions);
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
                $discount -= (float)$position['netprice'];
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
     * @param Detail $detail
     * @return array
     */
    private function convertOrderDetailToLineData(Detail $detail)
    {
        $lineData = [];
        $lineData['id'] = $detail->getId();
        $lineData['ean'] = $detail->getEan();
        $lineData['quantity'] = $detail->getQuantity();
        $lineData['netprice'] = $this->getNetPrice($detail);
        $lineData['articlename'] = $detail->getArticleName();
        $lineData['articleID'] = $detail->getArticleId();
        $lineData['modus'] = $detail->getMode();
        
        return $lineData;
    }
    
    /**
     *
     * @param Detail $detail
     * @return float
     */
    protected function getNetPrice(Detail $detail)
    {
        $taxRate = bcdiv($detail->getTaxRate(), 100, AvalaraSDKAdapter::BCMATH_SCALE);
        $taxRatePlusOne = bcadd($taxRate, 1.0, AvalaraSDKAdapter::BCMATH_SCALE);
        
        return (float)bcdiv($detail->getPrice(), $taxRatePlusOne, AvalaraSDKAdapter::BCMATH_SCALE);
    }

    /**
     * @return string | null
     */
    protected function getIncoterm() {
        if (!$attr = $this->orderContext->getAttribute()) {
            return null;
        }
        return $attr->getMoptAvalaraIncoterms();
    }

}
