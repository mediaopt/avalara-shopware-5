<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use LandedCostCalculationAPILib\Models\Item;

/**
 * Factory to create \LandedCostCalculationAPILib\Models\Item
 *
 */
class LandedCostItemFactory extends AbstractFactory
{
    /**
     * build Item-model based on passed in lineData
     * 
     * @param mixed $lineData
     * @return \LandedCostCalculationAPILib\Models\Item | null
     */
    public function build($lineData)
    {
        /* @var $line \LandedCostCalculationAPILib\Models\Item */
        if (!$hsCode = $this->getHsCode($lineData)) {
            return null;
        }
        $line = new \stdClass();
        $line->id = $lineData['id'];
        $line->price = (float)$lineData['netprice'];
        $line->quantity = (float)$lineData['quantity'];
        $line->extendedPrice = $lineData['quantity'] * $lineData['netprice'];
        $line->description = $lineData['articlename'];
        $line->hsCode = $hsCode;
        $line->units = [];
        
        return $line;
    }

    protected function getHsCode($lineData)
    {
        $articleId = $lineData['articleID'];
        
        //load model
        if (!$article = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($articleId)) {
            return null;
        }

        //directly assigned to article ?
        if ($hsCode = $article->getAttribute()->getMoptAvalaraHscode()) {
            return $hsCode;
        }

        //category assignment ?
        foreach ($article->getCategories() as $category) {
            if ($hsCode = $category->getAttribute()->getMoptAvalaraHscode()) {
                return $hsCode;
            }
        }

        return null;
    }
}
