<?php

namespace Shopware\Plugins\MoptAvalara\Model;

class User extends AbstractModel
{
    //required
    public $CustomerCode;
    //optional
    public $BusinessIdentificationNo;
    public $CustomerUsageType;
    function getCustomerCode()
    {
        return $this->CustomerCode;
    }

    function getBusinessIdentificationNo()
    {
        return $this->BusinessIdentificationNo;
    }

    function getCustomerUsageType()
    {
        return $this->CustomerUsageType;
    }

    function setCustomerCode($CustomerCode)
    {
        $this->CustomerCode = $CustomerCode;
    }

    function setBusinessIdentificationNo($BusinessIdentificationNo)
    {
        $this->BusinessIdentificationNo = $BusinessIdentificationNo;
    }

    function setCustomerUsageType($CustomerUsageType)
    {
        $this->CustomerUsageType = $CustomerUsageType;
    }


}