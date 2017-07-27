<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;

/**
 *
 * Abstract factory to generate requests to the AvalaraSDK
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
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
