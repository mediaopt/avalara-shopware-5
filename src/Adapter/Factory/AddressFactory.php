<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\AddressLocationInfo;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;

/**
 * Description of Config
 *
 */
class AddressFactory extends AbstractFactory
{
    const COUNTRY_CODE__US = 'US';
    const COUNTRY_CODE__CA = 'CA';
    
    /**
     * build Address-model based on delivery address
     * 
     * @return \Avalara\AddressLocationInfo
     */
    public function buildDeliveryAddress()
    {
        $user = $this->getUserData();
        $address = new AddressLocationInfo();
        $address->city = $user['shippingaddress']['city'];
        $address->country = $user['additional']['countryShipping']['countryiso'];
        $address->line1 = $user['shippingaddress']['street'];
        $address->postalCode = $user['shippingaddress']['zipcode'];
        $address->region = $user['additional']['stateShipping']['shortcode'];
        
        return $address;
    }
    
    /**
     * build Address-model based on delivery address
     * 
     * @return \Avalara\AddressLocationInfo
     */
    public function buildBillingAddress()
    {
        $user = $this->getUserData();
        $address = new AddressLocationInfo();
        $address->city = $user['billingaddress']['city'];
        $address->country = $user['additional']['country']['countryiso'];
        $address->line1 = $user['shippingaddress']['street'];
        $address->postalCode = $user['billingaddress']['zipcode'];
        $address->region = $user['additional']['state']['shortcode'];
        
        return $address;
    }
    
    /**
     * build Address-model based on delivery address
     * 
     * @param \Shopware\Models\Order\Order $order
     * @return \Avalara\AddressLocationInfo
     */
    public function buildDeliveryAddressFromOrder(\Shopware\Models\Order\Order $order)
    {
        $address = new AddressLocationInfo();
        $address->city = $order->getShipping()->getCity();
        $address->country = $order->getShipping()->getCountry()->getIso();
        $address->line1 = $order->getShipping()->getStreet();
        $address->postalCode = $order->getShipping()->getZipCode();
        
        //get region
        $sql = "SELECT shortcode FROM "
                . "s_core_countries_states a "
                . "INNER JOIN s_order_shippingaddress b "
                . "ON a.id = b.stateID "
                . "WHERE b.id = " . $order->getShipping()->getId();
        $address->region = Shopware()->Db()->fetchOne($sql);
        
        return $address;
    }
    
    /**
     * build Address-model based on delivery address
     * 
     * @param \Shopware\Models\Order\Order $order
     * @return \Avalara\AddressLocationInfo
     */
    public function buildBillingAddressFromOrder(\Shopware\Models\Order\Order $order)
    {
        $address = new AddressLocationInfo();
        $address->city = $order->getBilling()->getCity();
        $address->country = $order->getBilling()->getCountry()->getIso();
        $address->line1 = $order->getBilling()->getStreet();
        $address->postalCode = $order->getBilling()->getZipCode();

        //get region
        $sql = "SELECT shortcode FROM "
                . "s_core_countries_states a "
                . "INNER JOIN s_order_billingaddress b "
                . "ON a.id = b.stateID "
                . "WHERE b.id = " . $order->getBilling()->getId();
        $address->region = Shopware()->Db()->fetchOne($sql);
        
        return $address;
    }
    
    /**
     * Origination (ship-from) address
     * @return \Avalara\AddressLocationInfo
     */
    public function buildOriginAddress()
    {
        $address = new AddressLocationInfo();
        $address->line1 = $this->getPluginConfig(FormCreator::ORIGIN_ADDRESS_LINE_1_FIELD);
        $address->line2 = $this->getPluginConfig(FormCreator::ORIGIN_ADDRESS_LINE_2_FIELD);
        $address->line3 = $this->getPluginConfig(FormCreator::ORIGIN_ADDRESS_LINE_3_FIELD);
        $address->city = $this->getPluginConfig(FormCreator::ORIGIN_CITY_FIELD);
        $address->postalCode = $this->getPluginConfig(FormCreator::ORIGIN_POSTAL_CODE_FIELD);
        $address->region = $this->getPluginConfig(FormCreator::ORIGIN_REGION_FIELD);
        $address->country = $this->getPluginConfig(FormCreator::ORIGIN_COUNTRY_FIELD);
        
        if (strlen($address->country) > 2) {
            $this->fixCountryCode($address);
        }
        
        return $address;
    }
    
    /**
     * Change country name to ISO code
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    private function fixCountryCode(AddressLocationInfo $address)
    {
        $country = strtolower($address->country);

        $formCreator = new FormCreator($this->getAdapter()->getBootstrap());
        foreach ($formCreator->getCountriesISO() as $item) {
            if ($country === strtolower($item[1])) {
                $address->country = $item[0];
                break;
            }
        }
        
        return $this;
    }
}
