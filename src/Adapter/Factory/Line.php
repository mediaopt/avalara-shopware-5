<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

/**
 * Description of Config
 *
 */
class Line extends AbstractFactory
{

    const ARTICLEID__SHIPPING = 'shipping';
    const ARTICLEID__VOUCHER = 'voucher';

    /**
     * build Line-model based on passed in lineData
     * 
     * @param mixed $lineData
     * @return \Shopware\Plugins\MoptAvalara\Model\Line
     */
    public function build($lineData, $voucher = null)
    {
        $line = new \Shopware\Plugins\MoptAvalara\Model\Line();
        $line->setLineNo($lineData['id']);
        $line->setItemCode($lineData['ean']);
        $line->setQty($lineData['quantity']);
        $line->setAmount($this->getParamAmount($lineData));
        $line->setOriginCode('01');
        $line->setDestinationCode('03');
        $line->setDescription($lineData['articlename']);
        $line->setTaxCode($this->getParamTaxCode($lineData));
        $line->setDiscounted($this->isNotVoucherOrShipping($lineData));
        $line->setTaxIncluded($this->getParamIsTaxIncluded($lineData, $line));

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
        
        if ($lineData['modus'] == 2){
            $voucher = Shopware()->Models()->getRepository('\Shopware\Models\Voucher\Voucher')->find($lineData['articleID']);
            return $voucher->getAttribute()->getMoptAvalaraTaxcode();
        }
        $articleId = $lineData['articleID'];
        //shipping could have his own TaxCode
        if ($this->isShipping($lineData)) {
            $dispatchobject = Shopware()->Models()->getRepository('Shopware\Models\Dispatch\Dispatch')->find($lineData['dispatchID']);
            if ($dispatchobject->getAttribute()) {
                $taxCode = $dispatchobject->getAttribute()->getMoptAvalaraTaxcode();
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
        if ($this->isShipping($lineData) && !$this->getParamTaxCode($lineData)) {
            return true;
        }
        return false;
    }

    protected function isShipping($lineData)
    {
        return $lineData['id'] == self::ARTICLEID__SHIPPING;
    }

    protected function isNotVoucherOrShipping($lineData)
    {
        //voucher has modus 2
        if (($lineData['modus'] == 2) || ($this->isShipping($lineData)))
        {
            return false;
        }
        return true;
    }
}
