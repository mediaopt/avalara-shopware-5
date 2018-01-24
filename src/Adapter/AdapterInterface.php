<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter;

use Shopware\Models\Shop\Shop;
use Shopware\Models\Order\Order;

/**
 * Adapter interface for the Avalara SDK.
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
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
     * @return Shop
     */
    public function getShopContext();

    /**
     * @param Shop $shopContext
     * @return $this
     */
    public function setShopContext(Shop $shopContext);

    /**
     * @param Order $order
     * @return Shop
     */
    public function getShopContextFromOrder(Order $order);
    
    /**
     *
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    public function getBootstrap();
    
    /**
     * @param int $id
     * @return \Shopware\Models\Order\Order
     */
    public function getOrderById($id);
    
    /**
     * @param int $orderNumber
     * @return \Shopware\Models\Order\Order
     */
    public function getOrderByNumber($orderNumber);
    
    /**
     * @param string $docCode
     * @return \stdClass
     */
    public function getTransactionByDocCode($docCode);
}
