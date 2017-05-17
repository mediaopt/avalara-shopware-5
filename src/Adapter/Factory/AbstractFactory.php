<?php
namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;

/**
 * Description of AbstractFactory
 *
 */
abstract class AbstractFactory
{
    /**
     *
     * @var \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected $adapter;
    
    /**
     *
     * @var array
     */
    protected $userData = null;
    
    /**
     *
     * @var array
     */
    protected $pluginConfig = null;
    
    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     *
     * @return \Avalara\AvaTaxClient
     */
    public function getSdk()
    {
        return $this->adapter->getAvaTaxClient();
    }

    /**
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     *
     * @return array
     */
    protected function getUserData()
    {
        if ($this->userData !== null) {
            return $this->userData;
        }
        return $this->userData = Shopware()->Modules()->Admin()->sGetUserData();
    }
    
    /**
     *
     * @return array
     */
    protected function getPluginConfig($key)
    {
        return $this->getAdapter()->getPluginConfig($key);
    }
}
