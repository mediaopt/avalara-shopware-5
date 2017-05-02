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

    function setLineNo($LineNo)
    {
        $this->LineNo = $LineNo;
    }

    function setItemCode($ItemCode)
    {
        $this->ItemCode = $ItemCode;
    }

    function setQty($Qty)
    {
        $this->Qty = $Qty;
    }

    function setAmount($Amount)
    {
        $this->Amount = $Amount;
    }

    function setOriginCode($OriginCode)
    {
        $this->OriginCode = $OriginCode;
    }

    function setDestinationCode($DestinationCode)
    {
        $this->DestinationCode = $DestinationCode;
    }

    function setDescription($Description)
    {
        $this->Description = $Description;
    }

    function setTaxCode($TaxCode)
    {
        $this->TaxCode = $TaxCode;
    }

    function setCustomerUsageType($CustomerUsageType)
    {
        $this->CustomerUsageType = $CustomerUsageType;
    }

    function setDiscounted($Discounted)
    {
        $this->Discounted = $Discounted;
    }

    function setTaxIncluded($TaxIncluded)
    {
        $this->TaxIncluded = $TaxIncluded;
    }

    function setRef1($Ref1)
    {
        $this->Ref1 = $Ref1;
    }

    function setRef2($Ref2)
    {
        $this->Ref2 = $Ref2;
    }

}
