<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Shopware\Models\Dispatch\Dispatch;
use Avalara\LineItemModel;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
 */
class ShippingFactory extends AbstractFactory
{
    /**
     * @var string Article ID for a shipping
     */
    const ARTICLE_ID = 'Shipping';

    /**
     * @var string Avalara default taxcode for a voucher
     */
    const TAXCODE = 'FR010000';

    /**
     *
     * @var Dispatch
     */
    private $dispatchEntity;

    /**
     * build Line-model based on passed in lineData
     * @param int $id Dispatch entity id
     * @param float $price
     * @return LineItemModel
     */
    public function build($id, $price)
    {
        $line = new LineItemModel();
        $line->number = self::ARTICLE_ID;
        $line->itemCode = self::ARTICLE_ID;
        $line->amount = $price;
        $line->quantity = 1;
        $line->description = self::ARTICLE_ID;
        $line->taxCode = $this->getTaxCode($id);
        $line->discounted = false;
        $line->taxIncluded =
            !$this->getAdapter()->getPluginConfig(Form::ADD_SHIPPING_TAX_TO_SHIPPING_COST) && $this->isTaxIncluded();

        return $line;
    }

    /**
     *
     * @param int $id
     * @return string
     */
    protected function getTaxCode($id)
    {
        if (!$dispatchObject = $this->getShippingEntity($id)) {
            return self::TAXCODE;
        }
        $attr = $dispatchObject->getAttribute();
        if ($attr && $attr->getMoptAvalaraTaxcode()) {
            return $attr->getMoptAvalaraTaxcode();
        }

        return self::TAXCODE;
    }

    /**
     *
     * @param int $id
     *
     * @return Dispatch | null
     */
    protected function getShippingEntity($id)
    {
        if (!$id) {
            return null;
        }
        if (null === $this->dispatchEntity) {
            $this->dispatchEntity = Shopware()
                ->Models()
                ->getRepository(Dispatch::class)
                ->find($id)
            ;
        }

        return $this->dispatchEntity;
    }

    /**
     *
     * @param int $id
     * @return boolean
     */
    public function isShippingInsured($id)
    {
        if (!$id) {
            return false;
        }

        $shippingEntity = $this->getShippingEntity($id);
        if (!$shippingEntity || !$attr = $shippingEntity->getAttribute()) {
            return false;
        }

        return $attr->getMoptAvalaraInsured();
    }

    /**
     *
     * @param int $id
     * @return boolean
     */
    public function isShippingExpress($id)
    {
        if (!$id) {
            return false;
        }

        $shippingEntity = $this->getShippingEntity($id);
        if (!$shippingEntity || !$attr = $shippingEntity->getAttribute()) {
            return false;
        }

        return (bool)$attr->getMoptAvalaraExpressShipping();
    }
}
