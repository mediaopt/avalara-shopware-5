<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Avalara\LineItemModel;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;

/**
 * Factory to create CreateTransactionModel from the bucket
 *
 */
class TransactionModelFactory extends AbstractFactory
{
    /**
     * 
     * @param string $docType
     * @param bool $isCommit
     * @return \Avalara\CreateTransactionModel
     */
    public function build($docType, $isCommit = false)
    {
        $user = $this->getUserData();

        $model = new CreateTransactionModel();
        $model->businessIdentificationNo = $user['billingaddress']['ustid'];
        $model->commit = $isCommit;
        $model->customerCode = $user['additional']['user']['id'];
        $model->date = date('Y-m-d', time());
        $model->discount = $this->getDiscount();
        $model->type = $docType;
        $model->currencyCode = Shopware()->Shop()->getCurrency()->getCurrency();
        $model->addresses = $this->getAddressesModel();
        $model->lines = $this->getLineModels();
        $model->companyCode = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::COMPANY_CODE_FIELD)
        ;
        $model->parameters = $this->getTransactionParameters();

        if (!empty($user['additional']['user']['mopt_avalara_exemption_code'])) {
            $model->exemptionNo = $user['additional']['user']['mopt_avalara_exemption_code'];
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
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress();
        
        return $addressesModel;
    }
    
    /**
     * 
     * @return LineItemModel[]
     */
    protected function getLineModels()
    {
        $lineFactory = $this->getLineFactory();
        $lines = [];
        $positions = Shopware()->Modules()->Basket()->sGetBasket();
        
        foreach ($positions['content'] as $position) {
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

        if ($shipment = $this->getShippingModel()) {
            $lines[] = $shipment;
        }

        if ($insurance = $this->getInsuranceModel($shipment)) {
            $lines[] = $insurance;
        }

        return $lines;
    }
    
    /**
     * 
     * @return float
     */
    protected function getDiscount()
    {
        $positions = Shopware()->Modules()->Basket()->sGetBasket();
        $discount = 0.0;
        
        foreach ($positions['content'] as $position) {
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
     *
     * @return array
     */
    protected function getShippingModel()
    {
        if (empty(Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsWithTax'])) {
            return null;
        }

        $lineFactory = $this->getLineFactory();
        $shippmentId = Shopware()->Session()->sOrderVariables['sDispatch']['id'];
        $cost = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsWithTax'];
        
        $line = new LineItemModel();
        $line->number = LineFactory::ARTICLEID_SHIPPING;
        $line->itemCode = LineFactory::ARTICLEID_SHIPPING;
        $line->amount = $cost;
        $line->quantity = 1;
        $line->description = LineFactory::ARTICLEID_SHIPPING;
        $line->taxCode = $lineFactory->getShippingTaxCode($shippmentId);
        $line->discounted = false;
        $line->taxIncluded = true;
        
        return $line;
    }
    
    /**
     * @param LineItemModel $shipmentLine
     * @return Source
     */
    protected function getInsuranceModel($shipmentLine = null) {
        $lineFactory = $this->getLineFactory();
        if (null === $shipmentLine) {
            return null;
        }
        $shippmentId = Shopware()->Session()->sOrderVariables['sDispatch']['id'];
        $shipping = $lineFactory->getShipping($shippmentId);
        $insurance = 0;
        if ($attr = $shipping->getAttribute()) {
            $insurance = (float)$attr->getMoptAvalaraInsured()
                ? $shipmentLine->amount
                : 0
            ;
        }
        
        $line = new LineItemModel();
        $line->number = LineFactory::ARTICLEID_INSURANCE;
        $line->itemCode = LineFactory::ARTICLEID_INSURANCE;
        $line->amount = $insurance;
        $line->quantity = 1;
        $line->description = LineFactory::ARTICLEID_INSURANCE;
        $line->taxCode = LineFactory::TAXCODE_INSUEANCE;
        $line->discounted = false;
        $line->taxIncluded = true;
        
        return $line;
    }
    
    /**
     * @return LineFactory
     */
    private function getLineFactory()
    {
        return $this->getAdapter()->getFactory('LineFactory');
    }
    
    /**
     * 
     * @return object
     */
    protected function getTransactionParameters()
    {
        $params = new \stdClass();
        
        $landedCostEnabled = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::LANDEDCOST_ENABLED_FIELD)
        ;
        if (!$landedCostEnabled) {
            return $params;
        }
        
        $defaultIncoterms = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::INCOTERMS_FIELD)
        ;
        
        $countryIncoterm = $this->getCountryIncoterm();
        $params->{LandedCostRequestParams::LANDED_COST_INCOTERMS} = ($countryIncoterm)
            ? $countryIncoterm
            : $defaultIncoterms
        ;
        
        return $params;
    }
    
    /**
     * @return string | null
     */
    private function getCountryIncoterm()
    {
        $user = $this->getUserData();
        $countryId = $user['additional']['countryShipping']['id'];
        /* @var $addressFactory AddressFactory */
        $addressFactory = $this->getAdapter()->getFactory('AddressFactory');
        if (!$country = $addressFactory->getDeliveryCountry($countryId)) {
            return null;
        }
        
        if (!$attr = $country->getAttribute()) {
            return null;
        }
        
        if ($incoterms = $attr->getMoptAvalaraIncoterms()) {
            return $incoterms;
        }
        
        return null;
    }
}
