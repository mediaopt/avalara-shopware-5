<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;

/**
 *
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
 */
class InsuranceFactory extends AbstractFactory
{
    /**
     * @var string Article ID for insurance
     */
    const ARTICLE_ID = 'Insurance';
    
    /**
     * @var string Default Avalara taxcode for insurance
     */
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
        $line->taxIncluded = $this->isTaxIncluded();
        
        return $line;
    }
}
