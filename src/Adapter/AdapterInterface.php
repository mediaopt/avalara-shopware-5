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
     * 
     * @return \Shopware\Plugins\MoptAvalara\Logger\LogSubscriber
     */
    public function getLogSubscriber();
    
    /**
     * @param string $type
     * @return \Shopware\Plugins\MoptAvalara\Service\AbstractService
     */
    public function getService($type);
    
    /**
     * @return \Avalara\AvaTaxClient
     */
    public function getAvaTaxClient();

    /**
     * @param string $key
     * @return mixed
     */
    public function getPluginConfig($key);
    
    /**
     * 
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    public function getBootstrap();
}
