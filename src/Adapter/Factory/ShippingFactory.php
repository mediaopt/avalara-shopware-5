<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;

/**
 * Factory to create \Avalara\LineItemModel
 *
 */
class ShippingFactory extends AbstractFactory
{
    const ARTICLE_ID = 'Shipping';
    const TAXCODE = 'FR010000';

    /**
     * build Line-model based on passed in lineData
     * @param int $id Dispatch entity id
     * @param float $price
     * @return \Avalara\LineItemModel
     */
    public function build($id, $price)
    {
        $line = new LineItemModel();
        $line->number = self::ARTICLE_ID;
        $line->itemCode = self::ARTICLE_ID;
        $line->amount = $price;
        $line->quantity = 1;
        $line->description = self::ARTICLE_ID;
        $line->taxCode = $this->getShippingTaxCode($id);
        $line->discounted = false;
        $line->taxIncluded = true;
        
        return $line;
    }

    /**
     * 
     * @param int $id
     * @return string
     */
    protected function getShippingTaxCode($id)
    {
        if (!$dispatchObject = $this->getShippingEntity($id)) {
            return self::TAXCODE;
        }
        $attr = $dispatchObject->getAttribute();
        if ($attr && $attr->getMoptAvalaraTaxcode()) {
            return $attr->getMoptAvalaraTaxcode();
        }

        return self::TAXCODE;
    }
    
    /**
     * 
     * @param int $id
     * @return \Shopware\Models\Dispatch\Dispatch | null
     */
    protected function getShippingEntity($id)
    {
        return Shopware()
            ->Models()
            ->getRepository('Shopware\Models\Dispatch\Dispatch')
            ->find($id)
        ;
    }
    
    /**
     * 
     * @param int $id
     * @return boolean
     */
    public function isShippingInsured($id) {
        $shippingEntity = $this->getShippingEntity($id);
        
        if (!$attr = $shippingEntity->getAttribute()) {
            return false;
        }
        
        return $attr->getMoptAvalaraInsured();
    }
}
