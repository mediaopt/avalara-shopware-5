<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\AddressLocationInfo;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;

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
        if ($region = $user['additional']['stateShipping']['shortcode']) {
            $address->region = $region;
        }

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
        if ($region = $this->getRegionById($order->getShipping()->getId())) {
            $address->region = $region;
        }
        
        return $address;
    }
    
    /**
     * Origination (ship-from) address
     * @return \Avalara\AddressLocationInfo
     */
    public function buildOriginAddress()
    {
        $address = new AddressLocationInfo();
        $address->line1 = $this->getPluginConfig(Form::ORIGIN_ADDRESS_LINE_1_FIELD);
        $address->line2 = $this->getPluginConfig(Form::ORIGIN_ADDRESS_LINE_2_FIELD);
        $address->line3 = $this->getPluginConfig(Form::ORIGIN_ADDRESS_LINE_3_FIELD);
        $address->city = $this->getPluginConfig(Form::ORIGIN_CITY_FIELD);
        $address->postalCode = $this->getPluginConfig(Form::ORIGIN_POSTAL_CODE_FIELD);
        $address->region = $this->getPluginConfig(Form::ORIGIN_REGION_FIELD);
        $address->country = $this->getPluginConfig(Form::ORIGIN_COUNTRY_FIELD);
        
        if (strlen($address->country) > 2) {
            $this->fixCountryCode($address);
        }
        
        if (strlen($address->region) > 3) {
            $this->fixRegionCode($address);
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

        $pluginConfigForm = new Form($this->getAdapter()->getBootstrap());
        foreach ($pluginConfigForm->getCountriesISO() as $item) {
            if ($country === strtolower($item[1])) {
                $address->country = $item[0];
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * Change region name to ISO code
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    private function fixRegionCode(AddressLocationInfo $address)
    {
        $countryIso = $address->country;
        $region = strtolower($address->region);
        $pluginConfigForm = new Form($this->getAdapter()->getBootstrap());
        foreach ($pluginConfigForm->getRegionsISO($countryIso) as $item) {
            if ($region === strtolower($item[1])) {
                $address->region = $item[0];
                break;
            }
        }
        
        return $this;
    }
    
    /**
     * 
     * @param int $id
     * @return string
     */
    private function getRegionById($id)
    {
        //get region
        $sql = "SELECT shortcode FROM "
            . "s_core_countries_states a "
            . "INNER JOIN s_order_shippingaddress b "
            . "ON a.id = b.stateID "
            . "WHERE b.id = " . $id
        ;

        return Shopware()->Db()->fetchOne($sql);
    }
    
    /**
     *
     * @param int $id
     * @return \Shopware\Models\Country\Country
     */
    public function getDeliveryCountry($id)
    {
        if (!$id) {
            throw new \Exception('Missing id for getDeliveryCountry');
        }
        return Shopware()
            ->Models()
            ->getRepository('\Shopware\Models\Country\Country')
            ->find($id)
        ;
    }
}
