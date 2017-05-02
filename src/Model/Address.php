<?php

namespace Shopware\Plugins\MoptAvalara\Model;

class Address extends AbstractModel
{

    const COUNTRY_CODE__US = 'US';
    const COUNTRY_CODE__CA = 'CA';

    public $AddressCode;
    public $Line1;
    public $Line2;
    public $Line3;
    public $City;
    public $Region;
    public $Country;
    public $PostalCode;

    public function getLine1()
    {
        return $this->Line1;
    }

    public function getLine2()
    {
        return $this->Line2;
    }

    public function getLine3()
    {
        return $this->Line3;
    }

    public function getCity()
    {
        return $this->City;
    }

    public function getRegion()
    {
        return $this->Region;
    }

    public function getPostalCode()
    {
        return $this->PostalCode;
    }

    public function getCountry()
    {
        return $this->Country;
    }

    public function setLine1($Line1)
    {
        $this->Line1 = $Line1;
    }

    public function setLine2($Line2)
    {
        $this->Line2 = $Line2;
    }

    public function setLine3($Line3)
    {
        $this->Line3 = $Line3;
    }

    public function setCity($City)
    {
        $this->City = $City;
    }

    public function setRegion($Region)
    {
        $this->Region = $Region;
    }

    public function setPostalCode($PostalCode)
    {
        $this->PostalCode = $PostalCode;
    }

    public function setCountry($Country)
    {
        $this->Country = $Country;
    }

    public function getAddressCode()
    {
        return $this->AddressCode;
    }

    public function setAddressCode($AddressCode)
    {
        $this->AddressCode = $AddressCode;
    }

}
