<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

/**
 * Description of GetTaxRequest
 *
 */
class GetTaxRequest extends AbstractFactory
{

    var $discount = 0;

    /**
     * 
     * @param string $docType
     * @param bool $isCommit
     * @return \Shopware\Plugins\MoptAvalara\Model\GetTaxRequest
     */
    public function build($docType, $isCommit)
    {
        $user = $this->getUserData();
        
        $address = $this->getAdapter()->getFactory('Address')->buildDeliveryAddress();
        $getTaxRequest = new \Shopware\Plugins\MoptAvalara\Model\GetTaxRequest();
        $getTaxRequest
            ->setAddresses(array($address))
            ->setCustomerCode($user['additional']['user']['id'])
            ->setDocDate(date('Y-m-d', time()))
            ->setDocType($docType)
            ->setCommit($isCommit)
            ->setCurrencyCode(Shopware()->Shop()->getCurrency()->getCurrency())
            ->setBusinessIdentificationNo($user['billingaddress']['ustid'])
            ->setLines($this->getParamLines())
            ->setDiscount($this->discount)
        ;
        
        if (!empty($user['additional']['user']['moptAvalaraExemptionCode'])) {
            $getTaxRequest->setExemptionNo($user['additional']['user']['moptAvalaraExemptionCode']);
        }

        return $getTaxRequest;
    }

    /**
     * 
     * @return array
     */
    protected function getParamAddresses()
    {
        /* @var $addressFactory Address */
        $addressFactory = $this->getAdapter()->getFactory('Address');

        /* @var $originAddress \Shopware\Plugins\MoptAvalara\Model\Address */
        $originAddress = $addressFactory->buildOriginAddress();
        //$originAddress->locationCode('01');

        /* @var $billingAddress \Shopware\Plugins\MoptAvalara\Model\Address */
        $billingAddress = $addressFactory->buildBillingAddress();
        //$billingAddress->locationCode('02');

        /* @var $deliveryAddress \Shopware\Plugins\MoptAvalara\Model\Address */
        $deliveryAddress = $addressFactory->buildDeliveryAddress();
        //$deliveryAddress->locationCode('03');

        return array($originAddress, $billingAddress, $deliveryAddress);
    }

    protected function getParamLines()
    {
        /* @var $lineFactory Line */
        $lineFactory = $this->getAdapter()->getFactory('Line');
        $lines = array();
        $positions = Shopware()->Modules()->Basket()->sGetBasket();
        foreach ($positions['content'] as $position) {
            if ($this->isDiscount($position['modus'])) {
                if ($this->isDiscountGlobal($position)) {
                    $this->discount -= floatval($position['netprice']);
                }
                else {
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

    /**
     * get shipment information
     *
     * @return array
     */
    protected function getShippingCharges()
    {
        if (!empty(Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsWithTax'])) {
            //create shipping item for compatibility reasons with line data
            $shippingItem = array();
            $shippingItem['id'] = Line::ARTICLEID__SHIPPING;
            $shippingItem['ean'] = '';
            $shippingItem['quantity'] = 1;
            //set grossprice as net => shipping will be transmitted as taxincluded = yes
            $shippingItem['netprice'] = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsNet'];
            $shippingItem['brutprice'] = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsWithTax'];
            $shippingItem['articlename'] = 'Shipping';
            $shippingItem['articleID'] = 0;
            $shippingItem['dispatchID'] = Shopware()->Session()->sOrderVariables['sDispatch']['id'];
            return $shippingItem;
        } else {
            return null;
        }
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
