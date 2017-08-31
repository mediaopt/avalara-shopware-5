<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class BasketSubscriber extends AbstractSubscriber
{
    /**
     * @var string Temporary tax ID to be used on checkout process
     */
    const TAX_ID = 'mopt_avalara__';
    
    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Basket_GetBasket_FilterResult' => 'onFilterBasket',
            'Shopware_Modules_Basket_getPriceForUpdateArticle_FilterPrice' => 'onGetPriceForUpdateArticle',
            'sArticles::getTaxRateByConditions::after' => 'afterGetTaxRateByConditions',
        ];
    }

    /**
     * Updates totals with LandedCost surcharge
     * @param \Enlight_Event_EventArgs $args
     */
    public function onFilterBasket(\Enlight_Event_EventArgs $args)
    {
        $session = $this->getSession();
        $newBasket = $args->getReturn();
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        $taxResult = $session->MoptAvalaraGetTaxResult;
        if(!$taxResult || !$service->isLandedCostEnabled()) {
            return $newBasket;
        }

        $landedCost = $service->getLandedCost($taxResult);
        $insurance = $service->getInsuranceCost($taxResult);
        $shippingCostSurcharge = bcadd($landedCost, $insurance, AvalaraSDKAdapter::BCMATH_SCALE);

        $newBasket['moptAvalaraShippingCostSurcharge'] = $shippingCostSurcharge;
        $newBasket['moptAvalaraLandedCost'] = $landedCost;
        $newBasket['moptAvalaraInsuranceCost'] = $insurance;
        $newBasket['moptAvalaraAmountWithoutLandedCost'] = $newBasket['Amount'];

        $toAppend = [
            'Amount',
            'AmountNet',
            'AmountNumeric',
            'AmountNetNumeric',
            'AmountWithTax',
            'AmountWithTaxNumeric'
        ];

        foreach ($toAppend as $prop) {
            $newBasket[$prop] = $this->addCostToValue($newBasket[$prop], $shippingCostSurcharge);
        }
        
        return $newBasket;
    }
    
    /**
     * 
     * @param mixed $value
     * @param float $cost
     * @return mixed
     */
    private function addCostToValue($value, $cost)
    {
        if (!$value) {
            return $value;
        }
        
        if (is_string($value)) {
            $float = str_replace(',', '.', $value);
            return str_replace('.', ',', (bcadd($float, $cost, AvalaraSDKAdapter::BCMATH_SCALE)));
        }
        
        return (float)bcadd($value, $cost, AvalaraSDKAdapter::BCMATH_SCALE);
    }
    
    /**
     * set tax rate
     * @param \Enlight_Hook_HookArgs $args
     * @return float|null
     */
    public function afterGetTaxRateByConditions(\Enlight_Hook_HookArgs $args)
    {
        $taxId = $args->get('taxId');
        if (0 !== strpos($taxId, self::TAX_ID)) {
            return null;
        }

        return (float)substr($taxId, strlen(self::TAX_ID));
    }
    
    /**
     * Calculate tax for items in a basket
     * @param \Enlight_Event_EventArgs $args
     */
    public function onGetPriceForUpdateArticle(\Enlight_Event_EventArgs $args)
    {
        $newPrice = $args->getReturn();
        $adapter = $this->getAdapter();
        
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        if (!$service->isGetTaxEnabled()) {
            return $newPrice;
        }
        
        $session = $this->getSession();
        if (!$taxResult = $session->MoptAvalaraGetTaxResult) {
            return $newPrice;
        }
        
        $taxRate = $service->getTaxRateForOrderBasketId($taxResult, $args->get('id'));

        if (null === $taxRate) {
            //tax has to be present for all positions on checkout confirm
            $msg = 'No tax information for basket-position ' . $args->get('id');
            $adapter->getLogger()->info($msg);

            return $newPrice;
        }
        
        $newPrice['taxID'] = self::TAX_ID . $taxRate;
        $newPrice['tax_rate'] = $taxRate;
        $newPrice['tax'] = $service->getTaxForOrderBasketId($taxResult, $args->get('id'));

        return $newPrice;
    }
}
