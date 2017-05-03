<?php

namespace Shopware\Plugins\MoptAvalara\Model;

class Line extends AbstractModel
{

    public $LineNo;
    public $ItemCode;
    public $Qty;
    public $Amount;
    public $OriginCode;
    public $DestinationCode;
    public $Description;
    public $TaxCode;
    public $CustomerUsageType;
    public $Discounted;
    public $TaxIncluded;
    public $Ref1;
    public $Ref2;

    function getLineNo()
    {
        return $this->LineNo;
    }

    function getItemCode()
    {
        return $this->ItemCode;
    }

    function getQty()
    {
        return $this->Qty;
    }

    function getAmount()
    {
        return $this->Amount;
    }

    function getOriginCode()
    {
        return $this->OriginCode;
    }

    function getDestinationCode()
    {
        return $this->DestinationCode;
    }

    function getDescription()
    {
        return $this->Description;
    }

    function getTaxCode()
    {
        return $this->TaxCode;
    }

    function getCustomerUsageType()
    {
        return $this->CustomerUsageType;
    }

    function getDiscounted()
    {
        return $this->Discounted;
    }

    function getTaxIncluded()
    {
        return $this->TaxIncluded;
    }

    function getRef1()
    {
        return $this->Ref1;
    }

    function getRef2()
    {
        return $this->Ref2;
    }

    /**
     * 
     * @param string $LineNo
     * @return Line
     */
    function setLineNo($LineNo)
    {
        $this->LineNo = $LineNo;
        return $this;
    }

    /**
     * 
     * @param string $ItemCode
     * @return Line
     */
    function setItemCode($ItemCode)
    {
        $this->ItemCode = $ItemCode;
        return $this;
    }

    /**
     * 
     * @param string $Qty
     * @return Line
     */
    function setQty($Qty)
    {
        $this->Qty = $Qty;
        return $this;
    }

    /**
     * 
     * @param string $Amount
     * @return Line
     */
    function setAmount($Amount)
    {
        $this->Amount = $Amount;
        return $this;
    }

    /**
     * 
     * @param string $OriginCode
     * @return Line
     */
    function setOriginCode($OriginCode)
    {
        $this->OriginCode = $OriginCode;
        return $this;
    }

    /**
     * 
     * @param string $DestinationCode
     * @return Line
     */
    function setDestinationCode($DestinationCode)
    {
        $this->DestinationCode = $DestinationCode;
        return $this;
    }

    /**
     * 
     * @param string $Description
     * @return Line
     */
    function setDescription($Description)
    {
        $this->Description = $Description;
        return $this;
    }

    /**
     * 
     * @param string $TaxCode
     * @return Line
     */
    function setTaxCode($TaxCode)
    {
        $this->TaxCode = $TaxCode;
        return $this;
    }

    /**
     * 
     * @param string $CustomerUsageType
     * @return Line
     */
    function setCustomerUsageType($CustomerUsageType)
    {
        $this->CustomerUsageType = $CustomerUsageType;
        return $this;
    }

    /**
     * 
     * @param string $Discounted
     * @return Line
     */
    function setDiscounted($Discounted)
    {
        $this->Discounted = $Discounted;
        return $this;
    }

    /**
     * 
     * @param string $TaxIncluded
     * @return Line
     */
    function setTaxIncluded($TaxIncluded)
    {
        $this->TaxIncluded = $TaxIncluded;
        return $this;
    }

    /**
     * 
     * @param string $Ref1
     * @return Line
     */
    function setRef1($Ref1)
    {
        $this->Ref1 = $Ref1;
        return $this;
    }

    /**
     * 
     * @param string $Ref2
     * @return Line
     */
    function setRef2($Ref2)
    {
        $this->Ref2 = $Ref2;
        return $this;
    }

}
