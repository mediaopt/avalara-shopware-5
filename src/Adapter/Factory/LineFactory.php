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
        $line->amount = $this->getAmount($lineData);
        $line->quantity = $lineData['quantity'];
        $line->description = $lineData['articlename'];
        $line->taxCode = $this->getTaxCode($lineData);
        $line->discounted = self::isNotVoucher($lineData);
        $line->taxIncluded = false;
        $line->parameters = $this->getParams($lineData);

        return $line;
    }

    /**
     *
     * @param array $lineData
     * @return float
     */
    protected function getAmount($lineData)
    {
        $price = (float)str_replace(',', '.', $lineData['netprice']);
 
        return $price * (float)$lineData['quantity'];
    }

    /**
     *
     * @param array $lineData
     * @return string
     */
    protected function getTaxCode($lineData)
    {
        $articleId = $lineData['articleID'];
        if ($voucherTaxCode = $this->getVoucherTaxCode($articleId, $lineData['modus'])) {
            return $voucherTaxCode;
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
     * @param int $modus
     * @return string
     */
    private function getVoucherTaxCode($id, $modus)
    {
        if (self::MODUS_VOUCHER !== $modus) {
            return null;
        }
        
        $voucherRepository = Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Voucher\Voucher')
        ;
        if (!$voucher = $voucherRepository->find($id)) {
            return null;
        }

        return $voucher->getAttribute()->getMoptAvalaraTaxcode();
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
        if (self::MODUS_VOUCHER == $lineData['modus']) {
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
                break;
            }
        }

        return $params;
    }

    /**
     *
     * @param array $lineData
     * @return bool
     */
    protected function isTaxIncluded($lineData)
    {
        return !$this->getTaxCode($lineData);
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
     * @param array $position
     * @return bool
     */
    public static function isNotVoucher($position)
    {
        if ($position['modus'] != LineFactory::MODUS_VOUCHER || empty($position['articleID'])) {
            return true;
        }
        $voucher = Shopware()->Models()->getRepository('\Shopware\Models\Voucher\Voucher')->find($position['articleID']);
        
        return !$voucher || !$voucher->getStrict();
    }
}
