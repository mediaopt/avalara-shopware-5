<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Mail\AbstractZendMailFormatter;
use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;

/**
 * Description of CheckoutSubscriber
 *
 */
class SendOrderMailSubscriber extends AbstractSubscriber
{
    /**
     *
     * @var AbstractZendMailFormatter[]
     */
    private $mailFormatters = [];
    
    /**
     * @param AbstractZendMailFormatter $mailFormatter
     * @return SendOrderMailSubscriber
     */
    public function addMailFormatter(AbstractZendMailFormatter $mailFormatter)
    {
        $this->mailFormatters[] = $mailFormatter;
        return $this;
    }
    
    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Order_SendMail_Filter' => 'onFilterSendOrderMail',
        ];
    }

    /**
     * Adds new lines into mail body
     * @param \Enlight_Event_EventArgs $args
     * @return \Zend_Mail
     */
    public function onFilterSendOrderMail(\Enlight_Event_EventArgs $args)
    {
        /* @var $mail \Zend_Mail */
        $mail = $args->getReturn();
        try {
            $context = $this->updateContext($args->get('context'));
            foreach ($this->mailFormatters as $mailFormatter) {
                $mailFormatter->updateMail($mail, $context);
            }
        } catch (\Exception $e) {
            $this->getAdapter()->getLogger()->critical($e->getMessage());
        }
        
        return $mail;
    }

    /**
     *
     * @param array $context
     * @return array
     */
    private function updateContext($context)
    {
        $adapter = $this->getAdapter();
        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        $taxResult = $this->getSession()->MoptAvalaraGetTaxResult;
        
        $landedCost = $service->getLandedCost($taxResult);
        $insurance = $service->getInsuranceCost($taxResult);
        $shippingCostSurcharge = bcadd($landedCost, $insurance, AvalaraSDKAdapter::BCMATH_SCALE);

        $context['moptAvalaraShippingCostSurcharge'] = 0;
        $context['moptAvalaraLandedCost'] = 0;
        $context['moptAvalaraInsuranceCost'] = 0;
        
        if ((float)$shippingCostSurcharge <= 0) {
            return $context;
        }

        /* @var $shop \Shopware\Models\Shop\DetachedShop */
        $shop = $this->getContainer()->get('Shop');
        $context['sShopURL'] = 'http://' . $shop->getHost() . $shop->getBasePath();
        $context['moptAvalaraShippingCostSurcharge'] = $shippingCostSurcharge;
        $context['moptAvalaraLandedCost'] = $landedCost;
        $context['moptAvalaraInsuranceCost'] = $insurance;

        return $context;
    }
}
