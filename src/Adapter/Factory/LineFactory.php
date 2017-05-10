<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\LineItemModel;

/**
 * Factory to create \Avalara\LineItemModel
 *
 */
class LineFactory extends AbstractFactory
{

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
        if ($lineData['modus'] == 2){
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
        return $this->isShipping($lineData) && !$this->getParamTaxCode($lineData);
    }

    protected function isShipping($lineData)
    {
        return $lineData['id'] == self::ARTICLEID__SHIPPING;
    }

    protected function isNeitherVoucherNorShipping($lineData)
    {
        //voucher has modus 2
        return $lineData['modus'] !== 2 && !$this->isShipping($lineData);
    }
}
