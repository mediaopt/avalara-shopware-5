<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use LandedCostCalculationAPILib\Models\CalculateRequest;
use LandedCostCalculationAPILib\Models\Destination;
use LandedCostCalculationAPILib\Models\Source;
use LandedCostCalculationAPILib\Models\Shipping;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;

/**
 * Factory to create CalculateRequest model from the bucket
 *
 */
class LandedCostCalculateRequestFactory extends AbstractFactory
{
    /**
     * @return \Avalara\CreateTransactionModel
     */
    public function build()
    {
        $incoterms = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::INCOTERMS_FIELD)
        ;
        
        $entityType = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::TRANSACTION_TYPE_FIELD)
        ;
        
        /* @var $model \LandedCostCalculationAPILib\Models\CalculateRequest */
        $model = new \stdClass();
        $model->charges = ['duties'];
        $model->date = date('c', time());
        $model->currency = Shopware()->Shop()->getCurrency()->getCurrency();
        $model->destination = $this->getDestinationModel();
        $model->source = $this->getSourceModel();
        $model->shipping = $this->getShippingModel();
        $model->incoterms = $incoterms;
        $model->entityType = $entityType;
        $model->items = $this->getItemModels();
        
        return $model;
    }
    
    /**
     * 
     * @return Destination
     */
    private function getDestinationModel() {
        /* @var $addressFactory AddressFactory */
        $addressFactory = $this->getAdapter()->getFactory('AddressFactory');
        $address = $addressFactory->buildDeliveryAddress();

        $destination = new \stdClass();
        $destination->country = $address->country;
        if ($address->region) {
            $destination->region = $address->country . '-' . $address->region;
        }
        
        return $destination;
    }
    
    /**
     * 
     * @return Source
     */
    private function getSourceModel() {
        /* @var $addressFactory AddressFactory */
        $addressFactory = $this->getAdapter()->getFactory('AddressFactory');
        $address = $addressFactory->buildOriginAddress();

        $source = new Source();
        $source->country = $address->country;
        if ($address->region) {
            $source->region = $address->country . '-' . $address->region;
        }
        
        return $source;
    }
    
    /**
     * 
     * @return Source
     */
    private function getShippingModel() {
        if (empty(Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsNet'])) {
            return null;
        }
        
        /* @var $shipping \LandedCostCalculationAPILib\Models\Shipping */
        $shipping = new \stdClass();
        $shipping->express = false;
        $shipping->insurance = 0;
        
        $shipping->cost = (float)Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsNet'];
        
        if ($attr = $this->getDispatchFromBasket()->getAttribute()) {
            $shipping->express = (bool)$attr->getMoptAvalaraExpressShipping();
            $shipping->insurance = (float)($attr->getMoptAvalaraInsured())
                ? $shipping->cost
                : 0
            ;
        }
        
        return $shipping;
    }
    
    /**
     * 
     * @return \Shopware\Models\Dispatch\Dispatch
     */
    private function getDispatchFromBasket() {
        $dispatchId = Shopware()->Session()->sOrderVariables['sDispatch']['id'];
        
        return Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Dispatch\Dispatch')
            ->find($dispatchId)
        ;
    }
    
    private function getItemModels()
    {
        /* @var $lineFactory LandedCostItemFactory */
        $lineFactory = $this->getAdapter()->getFactory('LandedCostItemFactory');
        $items = [];
        $positions = Shopware()->Modules()->Basket()->sGetBasket();
        
        foreach ($positions['content'] as $position) {
            $item = $lineFactory->build($position);
            if (null === $item || LineFactory::isVoucher($position)) {
                continue;
            }

            $items[] = $lineFactory->build($position);
        }
        
        return $items;
    }
}
