<?php

namespace Shopware\Plugins\MoptAvalara\Model;

class Address extends AbstractModel
{
    const COUNTRY_CODE__US = 'US';
    const COUNTRY_CODE__CA = 'CA';

    /**
     * @var string Line1
     */
    public $line1;

    /**
     * @var string Line2
     */
    public $line2;

    /**
     * @var string Line3
     */
    public $line3;

    /**
     * @var string City
     */
    public $city;

    /**
     * @var string State / Province / Region
     */
    public $region;

    /**
     * @var string Two character ISO 3166 Country Code
     */
    public $country;

    /**
     * @var string Postal Code / Zip Code
     */
    public $postalCode;
    
    public function getLine1()
    {
        return $this->line1;
    }

    public function getLine2()
    {
        return $this->line2;
    }

    public function getLine3()
    {
        return $this->line3;
    }

    public function getCity()
    {
        return $this->city;
    }

    public function getRegion()
    {
        return $this->region;
    }

    public function getPostalCode()
    {
        return $this->postalCode;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function setLine1($line1)
    {
        $this->line1 = $line1;
        return $this;
    }

    public function setLine2($line2)
    {
        $this->line2 = $line2;
        return $this;
    }

    public function setLine3($line3)
    {
        $this->line3 = $line3;
        return $this;
    }

    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    public function setRegion($region)
    {
        $this->region = $region;
        return $this;
    }

    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function setCountry($country)
    {
        $this->country = $country;
        return $this;
    }
}
