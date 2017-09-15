<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;
use Shopware\Plugins\MoptAvalara\Util\BcMath;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
abstract class AbstractSubscriber implements SubscriberInterface
{
    /**
     *
     * @var \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    private $bootstrap;
    
    /**
     *
     * @var \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    private $adapter;
    
    /**
     *
     * @param \Shopware\Components\Form\Container
     */
    private $container;

    /**
     * @var BcMath
     */
    protected $bcMath;
    
    /**
     *
     * @param \Shopware_Plugins_Backend_MoptAvalara_Bootstrap $bootstrap
     */
    public function __construct(\Shopware_Plugins_Backend_MoptAvalara_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
        $this->bcMath = new BcMath();
    }

    /**
     *
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected function getAdapter()
    {
        if (null === $this->adapter) {
            $this->adapter = $this->getContainer()->get(AvalaraSDKAdapter::SERVICE_NAME);
        }
        
        return $this->adapter;
    }
    
    /**
     *
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    protected function getBootstrap()
    {
        return $this->bootstrap;
    }
    
    /**
     *
     * @return \Shopware\Components\Form\Container
     */
    protected function getContainer()
    {
        if (null === $this->container) {
            $this->container = Shopware()->Container();
        }
        
        return $this->container;
    }
    
    /**
     *
     * @return \Enlight_Components_Session_Namespace
     */
    protected function getSession()
    {
        return $this->getContainer()->get('session');
    }

    /**
     * Method returns array of shipping surcharges in this order:
     * 'shippingCostSurcharge' => float ($landedCost + $insurance)
     * 'landedCost' => float
     * 'insurance' => float
     *
     * @return float[]
     */
    protected function getShippingSurcharge()
    {
        $session = $this->getSession();
        $adapter = $this->getAdapter();

        /* @var $service \Shopware\Plugins\MoptAvalara\Service\GetTax */
        $service = $adapter->getService('GetTax');
        $taxResult = $session->MoptAvalaraGetTaxResult
            ?: $session->MoptAvalaraOnFinishGetTaxResult
        ;

        if (!$taxResult) {
            return [
                'shippingCostSurcharge' => 0.0,
                'landedCost'            => 0.0,
                'insurance'             => 0.0
            ];
        }

        $landedCost = $service->getLandedCost($taxResult);
        $insurance = $service->getInsuranceCost($taxResult);
        $shippingCostSurcharge = $this->bcMath->bcadd($landedCost, $insurance);

        return [
            'shippingCostSurcharge' => $shippingCostSurcharge,
            'landedCost'            => $landedCost,
            'insurance'             => $insurance
        ];
    }

    /**
     * @param mixed $shippingCost
     * @param float $surcharge
     * @return float
     */
    protected function getShippingWithoutSurcharge($shippingCost, $surcharge)
    {
        $shippingFloat = $this
            ->bcMath
            ->convertToFloat($shippingCost);

        return $this
            ->bcMath
            ->bcsub($shippingFloat, $surcharge);
    }
}
