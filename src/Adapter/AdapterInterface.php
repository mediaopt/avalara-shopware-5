<?php

namespace Shopware\Plugins\MoptAvalara\Adapter;

/**
 * Adapter interface for the Avalara SDK.
 */
interface AdapterInterface
{
    /**
     * 
     * @param string $type
     * @return \Shopware\Plugins\MoptAvalara\Adapter\Factory\AbstractFactory
     */
    public function getFactory($type);
    
    /**
     * @return \Monolog\Logger
     */
    public function getLogger();
    
    /**
     * @return \Avalara\AvaTaxClient
     */
    public function getClient();
}
