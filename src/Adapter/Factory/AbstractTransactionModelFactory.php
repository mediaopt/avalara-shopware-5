<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\InsuranceFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\AddressFactory;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;

/**
 * Factory to create CreateTransactionModel from the bucket
 *
 */
abstract class AbstractTransactionModelFactory extends AbstractFactory
{
    /**
     * 
     * @return \Avalara\AddressesModel
     */
    abstract protected function getAddressesModel();
    
    /**
     * 
     * @return float
     */
    abstract protected function getDiscount();
    
    /**
     * 
     * @return int
     */
    abstract protected function getShippingId();
    
    /**
     * 
     * @return float
     */
    abstract protected function getShippingPrice();
    
    /**
     * @param array $positions
     * @return LineItemModel[]
     */
    protected function getLineModels($positions)
    {
        $lineFactory = $this->getLineFactory();
        $lines = [];

        foreach ($positions as $position) {
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

        if ($shippingModel = $this->getShippingModel()) {
            $lines[] = $shippingModel;
        }

        if ($insurancModel = $this->getInsuranceModel($shippingModel)) {
            $lines[] = $insurancModel;
        }

        return $lines;
    }

    /**
     * get shipment information
     *
     * @return LineItemModel
     */
    protected function getShippingModel()
    {
        $price = $this->getShippingPrice();
        $shippmentId = $this->getShippingId();
        if (null === $price || null === $shippmentId) {
            return null;
        }
        
        return $this
            ->getShippingFactory()
            ->build($shippmentId, $price)
        ;
    }
    
    /**
     * @param LineItemModel $shippingModel
     * @return LineItemModel
     */
    protected function getInsuranceModel($shippingModel = null) {
        if (null === $shippingModel) {
            return null;
        }
        $shippingFactory = $this->getShippingFactory();
        $shippmentId = $this->getShippingId();
        
        if (!$price = $shippingFactory->isShippingInsured($shippmentId)) {
            return null;
        }
        
        return $this
            ->getInsuranceFactory()
            ->build($price)
        ;
    }

    /**
     * @return LineFactory
     */
    protected function getLineFactory()
    {
        return $this->getAdapter()->getFactory('LineFactory');
    }
    
    /**
     * @return ShippingFactory
     */
    protected function getShippingFactory()
    {
        return $this->getAdapter()->getFactory('ShippingFactory');
    }
    
    /**
     * @return InsuranceFactory
     */
    protected function getInsuranceFactory()
    {
        return $this->getAdapter()->getFactory('InsuranceFactory');
    }
    
    /**
     * @return AddressFactory
     */
    protected function getAddressFactory()
    {
        return $this->getAdapter()->getFactory('AddressFactory');
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
    protected function getCountryIncoterm()
    {
        $user = $this->getUserData();
        $countryId = $user['additional']['countryShipping']['id'];

        $addressFactory = $this->getAddressFactory();
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
    
    /**
     * 
     * @return string
     */
    protected function getCompanyCode()
    {
        return $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::COMPANY_CODE_FIELD)
        ;
    }
}
