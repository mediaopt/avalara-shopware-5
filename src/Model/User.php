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

    /**
     * 
     * @param string $CustomerCode
     * @return User
     */
    function setCustomerCode($CustomerCode)
    {
        $this->CustomerCode = $CustomerCode;
        return $this;
    }

    /**
     * 
     * @param string $BusinessIdentificationNo
     * @return User
     */
    function setBusinessIdentificationNo($BusinessIdentificationNo)
    {
        $this->BusinessIdentificationNo = $BusinessIdentificationNo;
        return $this;
    }

    /**
     * 
     * @param string $CustomerUsageType
     * @return User
     */
    function setCustomerUsageType($CustomerUsageType)
    {
        $this->CustomerUsageType = $CustomerUsageType;
        return $this;
    }


}