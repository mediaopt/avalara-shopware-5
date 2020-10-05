<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Shopware\Models\Article\Article;
use Shopware\Models\Voucher\Voucher;
use Avalara\LineItemModel;
use Shopware\Plugins\MoptAvalara\LandedCost\LandedCostRequestParams;
use Shopware\Plugins\MoptAvalara\Service\GetTax;

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
     * @return LineItemModel
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
        if ($voucherTaxCode = $this->getVoucherTaxCode($articleId, $lineData['ordernumber'], $lineData['modus'])) {
            return $voucherTaxCode;
        }
        
        //load model
        if (!$article = Shopware()->Models()->getRepository(Article::class)->find($articleId)) {
            return null;
        }

        //directly assigned to article ?
        if ($taxCode = $this->getTaxCodeFromAttr($article->getMainDetail()->getAttribute())) {
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
     * Retrieves the tax code for a voucher
     * @param int    $id        Article ID of the basket item, which is the primary ID of the voucher
     * @param string $orderCode Oordernumber of the basket item, which is the orderCode of the voucher
     * @param int    $modus     Modus for the basket item
     * @return string|null The related Avalara Tax Code or null
     */
    private function getVoucherTaxCode($id, $orderCode, $modus)
    {
        if (self::MODUS_VOUCHER !== (int)$modus) {
            return null;
        }
        
        $voucherRepository = Shopware()
            ->Models()
            ->getRepository(Voucher::class)
        ;

        /*
         * Find related voucher by orderCode
         * This is more precise, since SW uses the ID of the individual voucher code (s_emarketing_voucher_codes)
         * as article ID instead of the voucher itself.
         * Under certain circumstances searching voucher by ID could lead to incorrect results.
         */
        $voucher = $voucherRepository->findOneBy(['orderCode' => $orderCode]);

        // Find voucher by id
        if (!$voucher) {
            $voucher = $voucherRepository->find($id);
        }

        if (!$voucher) {
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
        if (!$article = Shopware()->Models()->getRepository(Article::class)->find($articleId)) {
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
        /* @var $service GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        if (!$service->isLandedCostEnabled()) {
            return null;
        }

        //directly assigned to article ?
        if ($hsCode = $this->getHsCodeFromAttr($article->getMainDetail()->getAttribute())) {
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
        if ((int)$position['modus'] !== self::MODUS_VOUCHER
            || (empty($position['articleID']) && empty($position['ordernumber']))
        ) {
            return true;
        }

        $vaucherRepository = Shopware()->Models()->getRepository(Voucher::class);

        // Find voucher by orderCode
        $voucher = $vaucherRepository->findOneBy(['orderCode' => $position['ordernumber']]);

        // Find voucher by ID
        if (!$voucher) {
            $voucher = $vaucherRepository->find($position['articleID']);
        }

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
