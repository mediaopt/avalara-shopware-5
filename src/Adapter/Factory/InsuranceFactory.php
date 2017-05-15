<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;

/**
 * Factory to create \Avalara\LineItemModel
 *
 */
class InsuranceFactory extends AbstractFactory
{
    const ARTICLE_ID = 'Insurance';
    const TAXCODE = 'FR070100';

    /**
     * build Line-model
     * 
     * @param float $price
     * @return \Avalara\LineItemModel
     */
    public function build($price)
    {
        $line = new LineItemModel();
        $line->number = self::ARTICLE_ID;
        $line->itemCode = self::ARTICLE_ID;
        $line->amount = $price;
        $line->quantity = 1;
        $line->description = self::ARTICLE_ID;
        $line->taxCode = self::TAXCODE;
        $line->discounted = false;
        $line->taxIncluded = true;
        
        return $line;
    }
}
