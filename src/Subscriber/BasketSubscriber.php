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

        if(!$taxResult = $session->MoptAvalaraGetTaxResult) {
            return $newBasket;
        }
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        if (!$landedCost = $service->getLandedCost($taxResult)) {
            return $newBasket;
        }
        
        $newBasket['moptAvalaraLandedCost'] = $landedCost;
        
        $toAppend = [
            'Amount',
            'AmountNet',
            'AmountNumeric',
            'AmountNetNumeric',
            'AmountWithTax',
            'AmountWithTaxNumeric'
        ];
        
        foreach ($toAppend as $prop) {
            if ($newBasket[$prop]) {
                $newBasket[$prop] += $landedCost;
            }
        }

        return $newBasket;
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
        $session = $this->getSession();
        if (!$taxResult = $session->MoptAvalaraGetTaxResult) {
            return;
        }

        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        $taxRate = $service->getTaxRateForOrderBasketId($taxResult, $args->get('id'));
        
        if (null === $taxRate) {
            //tax has to be present for all positions on checkout confirm
            $action = $args->getRequest()->getActionName();
            $controller = $args->getRequest()->getControllerName();
            if ('checkout' == $controller && 'confirm' == $action) {
                $msg = 'No tax information for basket-position ' . $args->get('id');
                $adapter->getLogger()->error($msg);
                //@todo Check if we should remove this
                //customer should not be warning if avalara is not working.
                //throw new \Exception($msg);
            }

            return;
        }
        
        $newPrice = $args->getReturn();
        $newPrice['taxID'] = 'mopt_avalara__' . $taxRate;
        $newPrice['tax_rate'] = $taxRate;
        $newPrice['tax'] = $service->getTaxForOrderBasketId($taxResult, $args->get('id'));

        return $newPrice;
    }
}
