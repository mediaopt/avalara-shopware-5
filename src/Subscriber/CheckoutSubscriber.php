<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Models\Order\Order;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class CheckoutSubscriber extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatchSecure_Frontend_Checkout' => 'onBeforeCheckoutConfirm',
            'sAdmin::sGetPremiumDispatch::after' => 'onAfterAdminGetPremiumDispatch',
            'Shopware_Controllers_Frontend_Checkout::getShippingCosts::after' => 'onAfterGetShippingCosts'
        ];
    }

    /**
     * call getTax service
     * @param \Enlight_Event_EventArgs $args
     */
    public function onBeforeCheckoutConfirm(\Enlight_Event_EventArgs $args)
    {
        $action = $args->getRequest()->getActionName();
        $this->changeShippingCostInView($args, $action);
        $this->requestAvalaraTax($args, $action);
    }

    /**
     *
     * @param \Enlight_Hook_HookArgs $args
     * @return string[]|void
     */
    public function onAfterAdminGetPremiumDispatch(\Enlight_Hook_HookArgs $args)
    {
        $return = $args->getReturn();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $this->getAdapter()->getService('GetTax');
        $session = $this->getSession();
        if (empty($session->MoptAvalaraGetTaxResult) || !$service->isGetTaxEnabled()) {
            return;
        }

        if ($service->isGetTaxDisabledForCountry()) {
            return;
        }

        $taxRate = $service->getTaxRateForOrderBasketId($session->MoptAvalaraGetTaxResult, ShippingFactory::ARTICLE_ID);
        
        if (!$taxRate) {
            return $return;
        }
        $return['tax_calculation'] = true;
        $return['tax_calculation_value'] = $taxRate;
        
        return $return;
    }

    /**
     * Method will add a delivery surcharge to shipping price
     * We should also save original shipping price for avalara request
     *
     * @param \Enlight_Hook_HookArgs $args
     * @return mixed[]
     * @throws \RuntimeException
     */
    public function onAfterGetShippingCosts(\Enlight_Hook_HookArgs $args)
    {
        $shippingCost = $this->saveOriginalShippingCost($args);

        return $this->addSurchargesToShippingCost($shippingCost);
    }

    /**
     * Method will decrease a shipping cost in view
     *
     * @param \Enlight_Event_EventArgs $args
     * @param string $action
     */
    private function changeShippingCostInView(\Enlight_Event_EventArgs $args, $action)
    {
        if (!in_array($action, ['confirm', 'finish', 'shippingPayment'], true)) {
            return;
        }

        /** @var $request \Enlight_Controller_Request_RequestHttp */
        $view = $args->getSubject()->View();
        $surcharges = $this->getShippingSurcharges();
        $view->assign('moptAvalaraLandedCost', $surcharges['landedCost']);
        $view->assign('moptAvalaraInsuranceCost', $surcharges['insurance']);

        $surcharge = $surcharges['shippingCostSurcharge'];
        if ($shippingCost = $view->getAssign('sShippingcosts')) {
            $shippingWithoutSurcharge = $this
                ->getShippingWithoutSurcharge($shippingCost, $surcharge)
            ;
            $view->assign('sShippingcosts', $shippingWithoutSurcharge);
        }
    }

    /**
     * @param \Enlight_Event_EventArgs $args
     * @param string $action
     */
    private function requestAvalaraTax(\Enlight_Event_EventArgs $args, $action)
    {
        if (!in_array($action, ['confirm', 'shippingPayment'], true)) {
            return;
        }

        $session = $this->getSession();
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');

        try {
            /* @var $model \Avalara\CreateTransactionModel */
            $model = $adapter->getFactory('OrderTransactionModelFactory')->build();

            if (!$service->isGetTaxCallAvailable($model, $this->getSession())
                || empty($args->getSubject()->View()->sUserLoggedIn)
            ) {
                $adapter->getLogger()->info('GetTax call for current basket already done / not enabled.');
                return;
            }

            $adapter->getLogger()->info('GetTax for current basket.');
            $response = $service->calculate($model);

            $session->MoptAvalaraGetTaxResult = $service->generateTaxResultFromResponse($response);
            $session->MoptAvalaraGetTaxRequestHash = $service->getHashFromRequest($model);
            //Trigger the same action again so the basket tax and landed cost could be applied in this request.
            $args->getSubject()->forward($action);
        } catch (\Exception $e) {
            $adapter->getLogger()->error('GetTax call failed: ' . $e->getMessage());
        }
    }

    /**
     * Save original shipping cost for Avalara Tax calculation
     *
     * @param \Enlight_Hook_HookArgs $args
     * @return mixed
     */
    private function saveOriginalShippingCost(\Enlight_Hook_HookArgs $args)
    {
        $shippingCost = $args->getReturn();
        $session = $this->getSession();
        $session->moptAvalaraShippingcostsNetOrigin = $shippingCost['netto'];

        return $shippingCost;
    }

    /**
     * @param mixed[] $shippingCost
     * @return mixed[]
     */
    private function addSurchargesToShippingCost($shippingCost)
    {
        $surcharges = $this->getShippingSurcharges();
        $shippingCostSurcharge = $surcharges['shippingCostSurcharge'];
        if ($shippingCostSurcharge <= 0.0) {
            return $shippingCost;
        }

        $shippingCost['surcharge'] = $this
            ->bcMath
            ->bcadd($shippingCost['surcharge'], $shippingCostSurcharge);
        $shippingCost['brutto'] = $this
            ->bcMath
            ->bcadd($shippingCost['brutto'], $shippingCostSurcharge);

        $shippingCost['value'] = (string)$shippingCost['brutto'];
        $shippingCost['netto'] = $this
            ->bcMath
            ->calculateNetto($shippingCost['brutto'], $shippingCost['tax']);

        return $shippingCost;
    }
}
