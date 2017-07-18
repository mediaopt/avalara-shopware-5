<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

/**
 * Description of BasketSubscriber
 *
 */
class BasketSubscriber extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     *
     * @return array
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
     * Updates totals with DHL delivery subcharge
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
        $customsDuties = $landedCost + $insurance;

        $newBasket['moptAvalaraCustomsDuties'] = $customsDuties;
        $newBasket['moptAvalaraLandedCost'] = $landedCost;
        $newBasket['moptAvalaraInsuranceCost'] = $insurance;
        
        $toAppend = [
            'Amount',
            'AmountNet',
            'AmountNumeric',
            'AmountNetNumeric',
            'AmountWithTax',
            'AmountWithTaxNumeric'
        ];

        foreach ($toAppend as $prop) {
            $newBasket[$prop] = $this->addCostToValue($newBasket[$prop], $totalMagnifier);
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
            $float = (float)str_replace(',', '.', $value);
            return str_replace('.', ',', (string)($float + $cost));
        }
        return $value += $cost;
    }
    
    /**
     * set tax rate
     * @param \Enlight_Hook_HookArgs $args
     */
    public function afterGetTaxRateByConditions(\Enlight_Hook_HookArgs $args)
    {
        $taxId = $args->get('taxId');
        if (!preg_match('#^mopt_avalara__(.+)$#', $taxId, $matches)) {
            return;
        }
        return $matches[1];
    }
    
    /**
     * Calculate tax for items in a basket
     * @param \Enlight_Event_EventArgs $args
     */
    public function onGetPriceForUpdateArticle(\Enlight_Event_EventArgs $args)
    {
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        if (!$service->isGetTaxEnabled()) {
            return;
        }
        
        $session = $this->getSession();
        if (!$taxResult = $session->MoptAvalaraGetTaxResult) {
            return;
        }

        $taxRate = $service->getTaxRateForOrderBasketId($taxResult, $args->get('id'));
        
        if (null === $taxRate) {
            //tax has to be present for all positions on checkout confirm
            $action = $args->getRequest()->getActionName();
            $controller = $args->getRequest()->getControllerName();
            if ('checkout' == $controller && 'confirm' == $action) {
                $msg = 'No tax information for basket-position ' . $args->get('id');
                $adapter->getLogger()->error($msg);
            }

            $args->getReturn();
        }
        
        $newPrice = $args->getReturn();
        $newPrice['taxID'] = 'mopt_avalara__' . $taxRate;
        $newPrice['tax_rate'] = $taxRate;
        $newPrice['tax'] = $service->getTaxForOrderBasketId($taxResult, $args->get('id'));

        return $newPrice;
    }
}
