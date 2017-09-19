<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;

/**
 * Factory to create \Avalara\LineItemModel
 *
 */
class LineFactory extends AbstractFactory
{
    const MODUS_VOUCHER = 2;
    const MODUS_BASKET_DISCOUNT = 3;
    const MODUS_DISCOUNT = 4;
    
    const ARTICLEID__SHIPPING = 'shipping';
    const ARTICLEID__VOUCHER = 'voucher';

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
        $line->taxIncluded = $this->getParamIsTaxIncluded($lineData, $line);

        return $line;
    }

    protected function getParamAmount($lineData)
    {
        if ($this->isShipping($lineData) && $this->getParamIsTaxIncluded($lineData)) {
            return $lineData['brutprice'];
        }

        $price = $lineData['netprice'] * $lineData['quantity'];
        return $price;
    }

    protected function getParamTaxCode($lineData)
    {
        $articleId = $lineData['articleID'];
        if ($lineData['modus'] == self::MODUS_VOUCHER){
            $voucherRepository = Shopware()
                ->Models()
                ->getRepository('\Shopware\Models\Voucher\Voucher')
            ;
            $voucher = $voucherRepository->find($articleId);
            return $this->getTaxCodeFromAttr($voucher->getAttribute());
        }
        
        //shipping could have his own TaxCode
        if ($this->isShipping($lineData)) {
            $dispatchobject = Shopware()->Models()->getRepository('Shopware\Models\Dispatch\Dispatch')->find($lineData['dispatchID']);
            if ($dispatchobject->getAttribute()) {
                $taxCode = $this->getTaxCodeFromAttr($dispatchobject->getAttribute());
                return $taxCode;
            }
        }
        //load model
        if (!$article = Shopware()->Models()->getRepository('Shopware\Models\Article\Article')->find($articleId)) {
            return null;
        }

        //directly assigned to article ?
        if ($taxCode = $this->getTaxCodeFromAttr($article->getAttribute())) {
            return $taxCode;
        }

        //category assignment ?
        foreach ($article->getCategories() as $category) {
            if ($taxCode = $this->getTaxCodeFromAttr($category->getAttribute())) {
                return $taxCode;
            }
        }

        return null;
    }

    /*
     * if line is a Shipping without a TaxCode, overwrite Amount[netPrice] with [brutprice] and set TaxIncluded  
     */

    protected function getParamIsTaxIncluded($lineData)
    {
        return $this->isShipping($lineData) && !$this->getParamTaxCode($lineData);
    }

    protected function isShipping($lineData)
    {
        return $lineData['id'] == self::ARTICLEID__SHIPPING;
    }

    protected function isNeitherVoucherNorShipping($lineData)
    {
        //voucher has modus 2
        return $lineData['modus'] !== self::MODUS_VOUCHER && !$this->isShipping($lineData);
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
        ],
        true);
    }
    
    /**
     * 
     * @param array $position
     * @return bool
     */
    public static function isDiscountGlobal($position)
    {
        if ($position['modus'] != LineFactory::MODUS_VOUCHER) {
           return true; 
        }
        $voucher = Shopware()->Models()->getRepository('\Shopware\Models\Voucher\Voucher')->find($position['articleID']);
        
        return !$voucher || !$voucher->getStrict();
    }
    
    /**
     * 
     * @param Attribute $attr
     * @return string
     */
    protected function getTaxCodeFromAttr($attr = null)
    {
        if (null === $attr) {
            return null;
        }
        
        return $attr->getMoptAvalaraTaxcode();
    }
}
