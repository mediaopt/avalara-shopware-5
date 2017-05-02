<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Shopware\Plugins\MoptAvalara\Model\Address as AddressModel;
use Shopware\Plugins\MoptAvalara\Util\FormCreator;

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
     * @return \Shopware\Plugins\MoptAvalara\Model\Address
     */
    public function buildDeliveryAddress()
    {
        $user = $this->getUserData();
        $address = new AddressModel();
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
     * @return \Shopware\Plugins\MoptAvalara\Model\Address
     */
    public function buildBillingAddress()
    {
        $user = $this->getUserData();
        $address = new AddressModel();
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
     * @return \Shopware\Plugins\MoptAvalara\Model\Address
     */
    public function buildDeliveryAddressFromOrder(\Shopware\Models\Order\Order $order)
    {
        $address = new AddressModel();
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
     * @return \Shopware\Plugins\MoptAvalara\Model\Address
     */
    public function buildBillingAddressFromOrder(\Shopware\Models\Order\Order $order)
    {
        $address = new AddressModel();
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
     * @return \Shopware\Plugins\MoptAvalara\Model\Address
     * @todo: build from module config
     */
    public function buildOriginAddress()
    {
        $address = new AddressModel();
        $address->setLine1($this->getPluginConfig(FormCreator::ORIGIN_ADDRESS_LINE_1_FIELD));
        $address->setLine2($this->getPluginConfig(FormCreator::ORIGIN_ADDRESS_LINE_2_FIELD));
        $address->setLine3($this->getPluginConfig(FormCreator::ORIGIN_ADDRESS_LINE_3_FIELD));
        $address->setPostalCode($this->getPluginConfig(FormCreator::ORIGIN_POSTAL_CODE_FIELD));
        $address->setCity($this->getPluginConfig(FormCreator::ORIGIN_CITY_FIELD));
        $address->setRegion($this->getPluginConfig(FormCreator::ORIGIN_REGION_FIELD));
        $address->setCountry($this->getPluginConfig(FormCreator::ORIGIN_COUNTRY_FIELD));
        
        return $address;
    }
}
