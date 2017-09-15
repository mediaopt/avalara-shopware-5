<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Bootstrap\Database;
use Shopware_Plugins_Backend_MoptAvalara_Bootstrap as AvalaraBootstrap;

/**
 * Integrates the surcharges of Avalara into the PDF of the backend in the order.
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class DocumentSubscriber extends AbstractSubscriber
{
    /**
     * @const int
     */
    const PAGE_BREAK = 10;

    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Components_Document::assignValues::after' => 'onBeforeRenderDocument',
        ];
    }
    
    /**
     * Extend shopware invoice template and assign Avalara variables to view
     * 
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onBeforeRenderDocument(\Enlight_Hook_HookArgs $args)
    {
        $document = $args->getSubject();
        $this
            ->subtractLandedCostFromShippingCost($document)
            ->addLandedCostSurcharge($document)
            ->updatePages($document)
        ;
    }

    /**
     * @param \Shopware_Components_Document $document
     * @return DocumentSubscriber
     */
    private function subtractLandedCostFromShippingCost(\Shopware_Components_Document $document)
    {
        $orderSmartyObj = $document->_view->getVariable('Order');
        $orderData = $orderSmartyObj->value['_order'];

        $landedCost = $this->getLandedCostFromOrderData($orderData);
        $insurance = $this->getInsuranceFromOrderData($orderData);
        $surcharge = $this->bcMath->bcadd($landedCost, $insurance);

        foreach ($orderSmartyObj->value['_positions'] as $i => $position) {
            if (isset($position['id'])) {
                continue;
            }

            $orderSmartyObj->value['_positions'][$i] = $this
                ->subtractCost(
                    $position,
                    $surcharge
                )
            ;
        }

        return $this;
    }

    /**
     * @param \Shopware_Components_Document $document
     * @return DocumentSubscriber
     */
    private function addLandedCostSurcharge(\Shopware_Components_Document $document)
    {
        $orderSmartyObj = $document->_view->getVariable('Order');
        $orderData = $orderSmartyObj->value['_order'];
        
        $landedCost = $this->getLandedCostFromOrderData($orderData);
        $insurance = $this->getInsuranceFromOrderData($orderData);
        
        $this
            ->fixTax($orderSmartyObj)
            ->addPosition($orderSmartyObj, $insurance, 'insurance')
            ->addPosition($orderSmartyObj, $landedCost, 'landedCost')
        ;

        $positions = array_chunk($orderSmartyObj->value['_positions'], self::PAGE_BREAK, true);
        $document->_view->assign('Pages', $positions);

        return $this;
    }

    /**
     * @param \Shopware_Components_Document $document
     * @return DocumentSubscriber
     */
    private function updatePages(\Shopware_Components_Document $document)
    {
        $orderSmartyObj = $document->_view->getVariable('Order');
        $positions = array_chunk(
            $orderSmartyObj->value['_positions'],
            self::PAGE_BREAK,
            true)
        ;

        $document->_view->assign('Pages', $positions);

        return $this;
    }

    /**
     * @param array $position
     * @param float $surcharge
     * @return array
     */
    private function subtractCost($position, $surcharge)
    {
        $brutto = (float)$position['price'];
        $bruttoUpdated = $this
            ->bcMath
            ->bcsub($brutto, $surcharge)
        ;

        $nettoUpdated = $this
            ->bcMath
            ->calculateNetto($bruttoUpdated, $position['tax'])
        ;
        $position['price'] = $position['amount'] = $bruttoUpdated;
        $position['netto'] = $position['amount_netto'] = $nettoUpdated;

        return $position;
    }

    /**
     * @param array $orderData Order params and values
     * @return float
     */
    private function getLandedCostFromOrderData($orderData = [])
    {
        $orderAttrs = $orderData['attributes'];
        return isset($orderAttrs[Database::LANDEDCOST_FIELD])
            ? (float) $orderAttrs[Database::LANDEDCOST_FIELD]
            : 0.0
        ;
    }
    
    /**
     * @param array $orderData Order params and values
     * @return float
     */
    private function getInsuranceFromOrderData($orderData = [])
    {
        $orderAttrs = $orderData['attributes'];
        return isset($orderAttrs[Database::INSURANCE_FIELD])
            ? (float) $orderAttrs[Database::INSURANCE_FIELD]
            : 0.0
        ;
    }

    /**
     * Method will leave only 2 dec in tax
     * @param \Smarty_Variable $orderSmartyObj
     * @return DocumentSubscriber
     */
    private function fixTax(\Smarty_Variable $orderSmartyObj)
    {
        foreach ($orderSmartyObj->value['_positions'] as &$position) {
            $position['tax'] = number_format($position['tax'], 2);
        }
        return $this;
    }

    /**
     * @param \Smarty_Variable $orderSmartyObj
     * @param float $value
     * @param string $label
     * @return DocumentSubscriber
     */
    private function addPosition(\Smarty_Variable $orderSmartyObj, $value, $label)
    {
        if ($value <= 0) {
            return $this;
        }
        
        $position = [
            'id' => '',
            'articleordernumber' => '',
            'name' => $this->translateSnippet($label),
            'quantity' => '',
            'tax' => 0,
            'amount' => $value,
            'price' => $value,
            'netto' => $value,
            'amount_netto' => $value,
        ];
        
        $orderSmartyObj->value['_positions'][] = $position;
        
        return $this;
    }
    
    /**
     * @param string $snippet
     * @return string
     */
    protected function translateSnippet($snippet)
    {
        return Shopware()
            ->Container()
            ->get('snippets')
            ->getNamespace(AvalaraBootstrap::SNIPPETS_NAMESPACE)
            ->get($snippet)
        ;
    }
}
