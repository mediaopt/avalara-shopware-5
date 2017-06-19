<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Enlight\Event\SubscriberInterface;
use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;

/**
 * Description of AbstractSubscriber
 *
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
     *
     * @param \Shopware_Plugins_Backend_MoptAvalara_Bootstrap $bootstrap
     */
    public function __construct(\Shopware_Plugins_Backend_MoptAvalara_Bootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }
    
    /**
     * @return array
     */
    abstract public static function getSubscribedEvents();
    
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
     * 
     * @param int $id
     * @return \Shopware\Models\Order\Order
     */
    protected function getOrderById($id)
    {
        $respository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        return $respository->find($id);
    }
}
