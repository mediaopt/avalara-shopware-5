<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;

/**
 * Factory to create \Avalara\LineItemModel
 *
 */
class LineFactory extends AbstractFactory
{
    const MODUS_VOUCHER = 2;
    const MODUS_BASKET_DISCOUNT = 3;
    const MODUS_DISCOUNT = 4;
    
    const ARTICLEID_SHIPPING = 'Shipping';
    const ARTICLEID_INSURANCE = 'Insurance';
    const TAXCODE_SHIPPING = 'FR010000';
    const TAXCODE_INSUEANCE = 'FR070100';
    const ARTICLEID_VOUCHER = 'voucher';

    /**
     * build Line-model based on passed in lineData
     * 
     * @param mixed $lineData
     * @return \Avalara\LineItemModel
     */
    public function build($lineData)
    {
        $line = new LineItemModel();
        $line->number = $lineData['id'];
        $line->itemCode = $lineData['id'];
        $line->amount = $this->getParamAmount($lineData);
        $line->quantity = $lineData['quantity'];
        $line->description = $lineData['articlename'];
        $line->taxCode = $this->getParamTaxCode($lineData);
        $line->discounted = $this->isNeitherVoucherNorShipping($lineData);
        $line->taxIncluded = $this->getParamIsTaxIncluded($lineData);
        $line->parameters = $this->getParams($lineData);

        return $line;
    }

    /**
     * 
     * @param array $lineData
     * @return float
     */
    protected function getParamAmount($lineData)
    {
        if ($this->isShipping($lineData) && $this->getParamIsTaxIncluded($lineData)) {
            return $lineData['brutprice'];
        }

        $price = $lineData['netprice'] * $lineData['quantity'];
        return $price;
    }

    /**
     * 
     * @param array $lineData
     * @return string
     */
    protected function getParamTaxCode($lineData)
    {
        $articleId = $lineData['articleID'];
        if (self::MODUS_VOUCHER == $lineData['modus']){
            $voucherRepository = Shopware()
                ->Models()
                ->getRepository('\Shopware\Models\Voucher\Voucher')
            ;
            $voucher = $voucherRepository->find($articleId);
            
            return $voucher->getAttribute()->getMoptAvalaraTaxcode();
        }
        
        //shipping could have his own TaxCode
        if ($this->isShipping($lineData)) {
            return $this->getShippingTaxCode($lineData['dispatchID']);
        }
        
        //load model
        if (!$article = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($articleId)) {
            return null;
        }

        //directly assigned to article ?
        if ($taxCode = $article->getAttribute()->getMoptAvalaraTaxcode()) {
            return $taxCode;
        }

        //category assignment ?
        foreach ($article->getCategories() as $category) {
            if ($taxCode = $category->getAttribute()->getMoptAvalaraTaxcode()) {
                return $taxCode;
            }
        }

        return null;
    }
    
    /**
     * 
     * @param int $id
     * @return string
     */
    public function getShippingTaxCode($id)
    {
        if (!$dispatchobject = $this->getShipping($id)) {
            return self::TAXCODE_SHIPPING;
        }
        $attr = $dispatchobject->getAttribute();
        if ($attr && $attr->getMoptAvalaraTaxcode()) {
            return $attr->getMoptAvalaraTaxcode();
        }

        return self::TAXCODE_SHIPPING;
    }
    
    /**
     * 
     * @param int $id
     * @return \Shopware\Models\Dispatch\Dispatch | null
     */
    public function getShipping($id)
    {
        return Shopware()
            ->Models()
            ->getRepository('Shopware\Models\Dispatch\Dispatch')
            ->find($id)
        ;
    }

    /**
     * Will setup AvaTax.LandedCost.HTSCode
     * @param array $lineData
     * @return \stdClass
     */
    protected function getParams($lineData)
    {
        $articleId = $lineData['articleID'];
        $params = new \stdClass();
        if (self::MODUS_VOUCHER == $lineData['modus'] || $this->isShipping($lineData) || $this->isInsurance($lineData)){
            return $params;
        }
        
        //load model
        if (!$article = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($articleId)) {
            return $params;
        }

        //directly assigned to article ?
        if ($hsCode = $article->getAttribute()->getMoptAvalaraHscode()) {
            $params->{LandedCostRequestParams::LANDED_COST_HTSCODE} = $hsCode;
            return $params;
        }

        //category assignment ?
        foreach ($article->getCategories() as $category) {
            if ($categoryHsCode = $category->getAttribute()->getMoptAvalaraHscode()) {
                $params->{LandedCostRequestParams::LANDED_COST_HTSCODE} = $categoryHsCode;
            }
        }

        return $params;
    }

    /**
     * 
     * @param array $lineData
     * @return bool
     */
    protected function getParamIsTaxIncluded($lineData)
    {
        return $this->isInsurance($lineData) && $this->isShipping($lineData) && !$this->getParamTaxCode($lineData);
    }

    /**
     * 
     * @param array $lineData
     * @return bool
     */
    protected function isShipping($lineData)
    {
        return self::ARTICLEID_SHIPPING == $lineData['id'];
    }
    
    /**
     * 
     * @param array $lineData
     * @return bool
     */
    protected function isInsurance($lineData)
    {
        return self::ARTICLEID_INSURANCE == $lineData['id'];
    }

    /**
     * 
     * @param array $lineData
     * @return bool
     */
    protected function isNeitherVoucherNorShipping($lineData)
    {
        //voucher has modus 2
        return self::MODUS_VOUCHER !== $lineData['modus'] && !$this->isShipping($lineData) && !$this->isInsurance($lineData);
    }
    
    /**
     * 
     * @param int $modus
     * @return bool
     */
    public static function isDiscount($modus)
    {
        return in_array($modus, [
            self::MODUS_VOUCHER, 
            self::MODUS_BASKET_DISCOUNT, 
            self::MODUS_DISCOUNT,
        ]);
    }
    
    /**
     * 
     * @param array $lineData
     * @return bool
     */
    public static function isVoucher($lineData)
    {
        return self::MODUS_VOUCHER == $lineData['modus'];
    }
    
    /**
     * 
     * @param array $position
     * @return bool
     */
    public static function isNotVoucher($position)
    {
        if ($position['modus'] != LineFactory::MODUS_VOUCHER) {
           return true; 
        }
        $voucher = Shopware()->Models()->getRepository('\Shopware\Models\Voucher\Voucher')->find($position['articleID']);
        
        return !$voucher || !$voucher->getStrict();
    }
}
