<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Mail\AbstractZendMailFormatter;

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
     * @param array $context
     * @return array
     */
    private function updateContext($context)
    {
        /* @var $shop \Shopware\Models\Shop\DetachedShop */
        $shop = $this->getContainer()->get('Shop');
        $context['sShopURL'] = 'http://' . $shop->getHost() . $shop->getBasePath();
        $surcharges = $this->getShippingSurcharge();

        $context['moptAvalaraShippingCostSurcharge'] = $surcharges['shippingCostSurcharge'];
        $context['moptAvalaraLandedCost'] = $surcharges['landedCost'];
        $context['moptAvalaraInsuranceCost'] = $surcharges['insurance'];
        
        if ((float)$surcharges['shippingCostSurcharge'] <= 0) {
            return $context;
        }

        $context['sShippingCosts'] = $this->updateShippingCostInContext($context, $surcharges['shippingCostSurcharge']);

        return $context;
    }

    /**
     * This method will extract avalara landed cost from shipping cost string.
     * $context['sShippingCosts'] should have a value like 12,9 EUR
     *
     * @param mixed[] $context
     * @param float $surcharge
     * @return string
     */
    private function updateShippingCostInContext($context, $surcharge)
    {
        $shippingCost = trim(
            explode(
                $context['sCurrency'],
                $context['sShippingCosts']
            )[0]
        );
        $shippingWithoutSurcharge = $this->getShippingWithoutSurcharge($shippingCost, $surcharge);

        return number_format($shippingWithoutSurcharge,2, ',', '')
            . ' ' . $context['sCurrency'];
    }
}
