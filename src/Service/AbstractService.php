<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;

/**
 * Description of AbstractService
 *
 */
abstract class AbstractService
{
    protected $config;
    
    /**
     *
     * @var \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    protected $adapter;
    
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
     * @return \Shopware\Plugins\MoptAvalara\Model\Config
     */
    protected function getConfig()
    {
        if ($this->config !== null) {
            return $this->config;
        }
        return $this->config = $this->adapter->getFactory('Config')->build();
    }

    /**
     * get adapter
     * 
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
