<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;

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
            'Shopware_Modules_Basket_getPriceForUpdateArticle_FilterPrice' => 'onGetPriceForUpdateArticle',
            'sArticles::getTaxRateByConditions::after' => 'afterGetTaxRateByConditions',
            'sBasket::sGetBasket::before' => 'onBeforeGetBasket',
            'sBasket::sAddVoucher::before' => 'onBeforeAddVoucher',
        ];
    }

    /**
     * set taxrate for discounts
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onBeforeGetBasket(\Enlight_Hook_HookArgs $args)
    {
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        $session = $this->getSession();

        if (empty($session->MoptAvalaraGetTaxResult) || !$service->isGetTaxEnabled()) {
            return;
        }

        if ($service->isGetTaxDisabledForCountry()) {
            return;
        }

        //abfangen voucher mode==2 strict=1 => eigene TaxRate zuweisen aus Avalara Response
        $taxResult = $session->MoptAvalaraGetTaxResult;
        if ($taxResult->totalTaxable < 0.0) {
            return;
        }

        $taxRate = $this->bcMath->bcdiv($taxResult->totalTax, $taxResult->totalTaxable);

        $config = Shopware()->Config();
        $config['sDISCOUNTTAX'] = $this->bcMath->bcmul($taxRate, 100);
        $config['sTAXAUTOMODE'] = false;
    }

    /**
     *
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onBeforeAddVoucher(\Enlight_Hook_HookArgs $args)
    {
        $session = $this->getSession();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        if (empty($session->MoptAvalaraGetTaxResult) || !$service->isGetTaxEnabled()) {
            return;
        }

        if ($service->isGetTaxDisabledForCountry()) {
            return;
        }

        $voucherCode = strtolower(stripslashes($args->get('voucherCode')));

        // Load the voucher details
        $voucherDetails = Shopware()->Db()->fetchRow(
            'SELECT *
              FROM s_emarketing_vouchers
              WHERE modus != 1
              AND LOWER(vouchercode) = ?
              AND (
                now() BETWEEN valid_from AND valid_to
                OR valid_to IS NULL
              )',
            [$voucherCode]
        ) ?: [];

        if (empty($voucherDetails['strict'])) {
            return;
        }

        //get tax rate for voucher
        $taxRate = $service->getTaxRateForOrderBasketId($session->MoptAvalaraGetTaxResult, LineFactory::ARTICLEID_VOUCHER);
        if (!$taxRate) {
            return;
        }
        $config = Shopware()->Config();
        $config['sVOUCHERTAX'] = $taxRate;
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
     *
     * @param \Enlight_Event_EventArgs $args
     * @return array
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

        if ($service->isGetTaxDisabledForCountry()) {
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
