<?php

namespace Mediaopt\Avalara\Adapter\Factory;

/**
 * Description of Config
 *
 */
class Address extends AbstractFactory
{
    /**
     * build Address-model based on delivery address
     * 
     * @param mixed $addressData
     * @return \Mediaopt\Avalara\Sdk\Model\Address
     */
    public function buildDeliveryAddress()
    {
        $user = $this->getUserData();
        $address = new \Mediaopt\Avalara\Sdk\Model\Address();
        $address->setCity($user['shippingaddress']['city']);
        $address->setCountry($user['additional']['countryShipping']['countryiso']);
        $address->setLine1($user['shippingaddress']['street']);
        $address->setPostalCode($user['shippingaddress']['zipcode']);
        $address->setRegion($user['additional']['stateShipping']['shortcode']);
        
        return $address;
    }
    
    /**
     * build Address-model based on delivery address
     * 
     * @param mixed $addressData
     * @return \Mediaopt\Avalara\Sdk\Model\Address
     */
    public function buildBillingAddress()
    {
        $user = $this->getUserData();
        $address = new \Mediaopt\Avalara\Sdk\Model\Address();
        $address->setCity($user['billingaddress']['city']);
        $address->setCountry($user['additional']['country']['countryiso']);
        $address->setLine1($user['shippingaddress']['street']);
        $address->setPostalCode($user['billingaddress']['zipcode']);
        $address->setRegion($user['additional']['state']['shortcode']);
        
        return $address;
    }
    
    /**
     * build Address-model based on delivery address
     * 
     * @param \Shopware\Models\Order\Order $order
     * @return \Mediaopt\Avalara\Sdk\Model\Address
     */
    public function buildDeliveryAddressFromOrder(\Shopware\Models\Order\Order $order)
    {
        $address = new \Mediaopt\Avalara\Sdk\Model\Address();
        $address->setCity($order->getShipping()->getCity());
        $address->setCountry($order->getShipping()->getCountry()->getIso());
        $address->setLine1($order->getShipping()->getStreet());
        $address->setPostalCode($order->getShipping()->getZipCode());
        
        //get region
        $sql = "SELECT shortcode FROM "
                . "s_core_countries_states a "
                . "INNER JOIN s_order_shippingaddress b "
                . "ON a.id = b.stateID "
                . "WHERE b.id = " . $order->getShipping()->getId();
        $address->setRegion(Shopware()->Db()->fetchOne($sql));
        
        return $address;
    }
    
    /**
     * build Address-model based on delivery address
     * 
     * @param \Shopware\Models\Order\Order $order
     * @return \Mediaopt\Avalara\Sdk\Model\Address
     */
    public function buildBillingAddressFromOrder(\Shopware\Models\Order\Order $order)
    {
        $address = new \Mediaopt\Avalara\Sdk\Model\Address();
        $address->setCity($order->getBilling()->getCity());
        $address->setCountry($order->getBilling()->getCountry()->getIso());
        $address->setLine1($order->getBilling()->getStreet());
        $address->setPostalCode($order->getBilling()->getZipCode());
        
        //get region
        $sql = "SELECT shortcode FROM "
                . "s_core_countries_states a "
                . "INNER JOIN s_order_billingaddress b "
                . "ON a.id = b.stateID "
                . "WHERE b.id = " . $order->getBilling()->getId();
        $address->setRegion(Shopware()->Db()->fetchOne($sql));
        
        return $address;
    }
    
    /**
     * Origination (ship-from) address
     * @return \Mediaopt\Avalara\Sdk\Model\Address
     * @todo: build from module config
     */
    public function buildOriginAddress()
    {
        $pluginConfig = $this->getPluginConfig();
        $address = new \Mediaopt\Avalara\Sdk\Model\Address();
        $address->setLine1($pluginConfig->mopt_avalara__origin_address__line1);
        $address->setLine2($pluginConfig->mopt_avalara__origin_address__line2);
        $address->setLine3($pluginConfig->mopt_avalara__origin_address__line3);
        $address->setPostalCode($pluginConfig->mopt_avalara__origin_address__postal_code);
        $address->setCity($pluginConfig->mopt_avalara__origin_address__city);
        $address->setRegion($pluginConfig->mopt_avalara__origin_address__region);
        $address->setCountry($pluginConfig->mopt_avalara__origin_address__country);
        
        return $address;
    }
}
