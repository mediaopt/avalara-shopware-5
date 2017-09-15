<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Shopware\Models\Article\Article;
use Avalara\LineItemModel;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;

/**
 *
 * Factory to create \Avalara\LineItemModel
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
 */
class LineFactory extends AbstractFactory
{
    /**
     * @var int Voucher modus
     */
    const MODUS_VOUCHER = 2;
    
    /**
     * @var int Basket discount modus
     */
    const MODUS_BASKET_DISCOUNT = 3;
    
    /**
     * @var int Discount modus
     */
    const MODUS_DISCOUNT = 4;

    /**
     * @var string Article ID for a voucher
     */
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
        
        return $this->bcMath->bcmul($price, $lineData['quantity']);
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

        return $this->getTaxCodeFromAttr($voucher->getAttribute());
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

        if ($hsCode = $this->getHsCode($article)) {
            $params->{LandedCostRequestParams::LANDED_COST_HTSCODE} = $hsCode;
        }

        return $params;
    }
    
    /**
     * @param Article $article
     * @return string
     */
    private function getHsCode(Article $article)
    {
        //directly assigned to article ?
        if ($hsCode = $this->getHsCodeFromAttr($article->getAttribute())) {
            return $hsCode;
        }

        //category assignment
        foreach ($article->getCategories() as $category) {
            if ($categoryHsCode = $this->getHsCodeFromAttr($category->getAttribute())) {
                return $categoryHsCode;
            }
        }
        
        return null;
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
        ], false);
    }

    /**
     *
     * @param array $position
     * @return bool
     */
    public static function isNotVoucher($position)
    {
        if ($position['modus'] != self::MODUS_VOUCHER || empty($position['articleID'])) {
            return true;
        }
        $voucher = Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Voucher\Voucher')
            ->find($position['articleID'])
        ;
        
        return !$voucher || !$voucher->getStrict();
    }
    
    /**
     * @param Attribute $attr
     * @return string
     */
    protected function getTaxCodeFromAttr($attr = null)
    {
        return null === $attr
            ? $attr
            : $attr->getMoptAvalaraTaxcode()
        ;
    }
    
    /**
     * @param Attribute $attr
     * @return string
     */
    protected function getHsCodeFromAttr($attr = null)
    {
        return null === $attr
            ? null 
            : $attr->getMoptAvalaraHscode()
        ;
    }
}
