<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

/**
 * Description of GetTaxRequest
 *
 */
class GetTaxRequestFromOrder extends AbstractFactory
{

    protected $order;
    var $discount = 0;

    public function build(\Shopware\Models\Order\Order $order)
    {
        $this->order = $order;
        /* @var $customer \Shopware\Models\Customer\Customer */
        $customer = $order->getCustomer();

        $getTaxRequest = new \Shopware\Plugins\MoptAvalara\Model\GetTaxRequest();
        $getTaxRequest
            ->setCustomerCode($customer->getId())
            ->setDocDate(date('Y-m-d', time()))
            ->setDocType(\Shopware\Plugins\MoptAvalara\Model\DocumentType::SALES_INVOICE)
            ->setCommit(true)
            ->setCurrencyCode($order->getCurrency())
            ->setBusinessIdentificationNo($customer->getBilling()->getVatId())
            ->setAddresses($this->getParamAddresses())
            ->setLines($this->getParamLines())
            ->setDiscount($this->discount)
            ->setDocCode($order->getNumber())
        ;


        if ($exemptionCode = $order->getCustomer()->getAttribute()->getMoptAvalaraExemptionCode()) {
            $getTaxRequest->setExemptionNo($exemptionCode);
        }

        return $getTaxRequest;
    }

    protected function getParamAddresses()
    {
        /* @var $addressFactory Address */
        $addressFactory = $this->getAdapterMain()->getFactory('Address');

        /* @var $originAddress \Shopware\Plugins\MoptAvalara\Model\Address */
        $originAddress = $addressFactory->buildOriginAddress();
        //$originAddress->locationCode('01');

        /* @var $billingAddress \Shopware\Plugins\MoptAvalara\Model\Address */
        $billingAddress = $addressFactory->buildBillingAddressFromOrder($this->order);
        //$billingAddress->locationCode('02');

        /* @var $deliveryAddress \Shopware\Plugins\MoptAvalara\Model\Address */
        $deliveryAddress = $addressFactory->buildDeliveryAddressFromOrder($this->order);
        //$deliveryAddress->locationCode('03');

        return array($originAddress, $billingAddress, $deliveryAddress);
    }

    protected function getParamLines()
    {
        /* @var $lineFactory Line */
        $lineFactory = $this->getAdapterMain()->getFactory('Line');
        $lines = array();

        foreach ($this->order->getDetails() as $position) {
            $position = $this->convertOrderDetailToLineData($position);
            if ($this->isDiscount($position['modus'])) {
                if ($this->isDiscountGlobal($position)) {
                    $this->discount -= floatval($position['netprice']);
                } else {
                    $position['id'] = Line::ARTICLEID__VOUCHER;
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
            $shippingItem = array();
            $shippingItem['id'] = Line::ARTICLEID__SHIPPING;
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
        $lineData = array();
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
