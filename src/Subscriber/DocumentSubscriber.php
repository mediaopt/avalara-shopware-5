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
 * Acts on PDF document creation for an order in backend
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class DocumentSubscriber extends AbstractSubscriber
{

    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Shopware_Components_Document::assignValues::after' => 'onBeforeRenderDocument',
        );
    }
    
    /**
     * extend shopware invoice template and assign paypal variables to view
     * 
     * @param \Enlight_Hook_HookArgs $args
     */
    public function onBeforeRenderDocument(\Enlight_Hook_HookArgs $args)
    {
        $document = $args->getSubject();
        $this->addCustomsDutiesToView($document);
        
        $this->updateDocument($document);
    }
    
    /**
     * @param \Shopware_Components_Document $document
     */
    private function addCustomsDutiesToView(\Shopware_Components_Document $document)
    {
        $orderSmartyObj = $document->_view->getVariable('Order');
        $orderData = $orderSmartyObj->value['_order'];
        
        $landedCost = $this->getLandedCostFromOrderData($orderData);
        $insurance = $this->getInsuranceFromOrderData($orderData);
        $customsDuties = $landedCost + $insurance;
        
        $this
            ->fixTax($orderSmartyObj)
            ->addPosition($orderSmartyObj, $landedCost, 'landedCost')
            ->addPosition($orderSmartyObj, $insurance, 'insurance')
        ;

        $positions = array_chunk($orderSmartyObj->value['_positions'], $document->_document['pagebreak'], true);
        $document->_view->assign('Pages', $positions);
        
        $orderSmartyObj->value['_amount'] += $customsDuties;
        $orderSmartyObj->value['_amountNetto'] += $customsDuties;
        $orderSmartyObj->value['_moptAvalaraLandedCost'] += $landedCost;
        $orderSmartyObj->value['_moptAvalaraInsurance'] += $insurance;
    }
    
    /**
     * Will update a document ammount value
     * @param \Shopware_Components_Document $document
     */
    private function updateDocument(\Shopware_Components_Document $document)
    {
        $orderSmartyObj = $document->_view->getVariable('Order');
        $orderData = $orderSmartyObj->value['_order'];

        $update = '
            UPDATE `s_order_documents` SET `amount` = ?
            WHERE orderID = ? LIMIT 1
            '
        ;
        
        Shopware()->Db()->query($update, [
            $orderSmartyObj->value['_amount'],
            $orderData['id'],
        ]);
    }
    
    /**
     * 
     * @param array $orderData
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
     * 
     * @param array $orderData
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
     * Will leave only 2 dec in tax
     * @param \Smarty_Variable $orderSmartyObj
     * @return DocumentSubscriber
     */
    private function fixTax(\Smarty_Variable $orderSmartyObj) {
        foreach ($orderSmartyObj->value['_positions'] as &$position) {
            $position['tax'] = number_format($position['tax'], 2);
        }
        return $this;
    }
    
    /**
     * 
     * @param \Smarty_Variable $orderSmartyObj
     * @param float $value
     * @param string $label
     * @return DocumentSubscriber
     */
    private function addPosition(\Smarty_Variable $orderSmartyObj, $value, $label)
    {
        if (!$value) {
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
     * 
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
