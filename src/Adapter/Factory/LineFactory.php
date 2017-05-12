<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;

/**
 * Factory to create \Avalara\LineItemModel
 *
 */
class LineFactory extends AbstractFactory
{
    const MODUS_VOUCHER = 2;
    const MODUS_BASKET_DISCOUNT = 3;
    const MODUS_DISCOUNT = 4;
    
    const ARTICLEID_SHIPPING = 'shipping';
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
            $dispatchobject = Shopware()->Models()->getRepository('Shopware\Models\Dispatch\Dispatch')->find($lineData['dispatchID']);
            if ($attr = $dispatchobject->getAttribute()) {
                $taxCode = $attr->getMoptAvalaraTaxcode();
                
                return $taxCode;
            }
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

    /*
     * if line is a Shipping without a TaxCode, overwrite Amount[netPrice] with [brutprice] and set TaxIncluded  
     */

    protected function getParamIsTaxIncluded($lineData)
    {
        return $this->isShipping($lineData) && !$this->getParamTaxCode($lineData);
    }

    protected function isShipping($lineData)
    {
        return self::ARTICLEID_SHIPPING == $lineData['id'];
    }

    protected function isNeitherVoucherNorShipping($lineData)
    {
        //voucher has modus 2
        return self::MODUS_VOUCHER !== $lineData['modus'] && !$this->isShipping($lineData);
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
    public static function isDiscountGlobal($position)
    {
        if ($position['modus'] != LineFactory::MODUS_VOUCHER) {
           return true; 
        }
        $voucher = Shopware()->Models()->getRepository('\Shopware\Models\Voucher\Voucher')->find($position['articleID']);
        
        return !$voucher || !$voucher->getStrict();
    }
}
