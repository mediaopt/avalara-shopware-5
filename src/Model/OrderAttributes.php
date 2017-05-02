<?php
namespace Shopware\Plugins\MoptAvalara\Model;

class OrderAttributes extends AbstractModel
{
    //required
    public $DocDate;
    //optional
    public $CurrencyCode;
    public $Discount;
    public $DocCode;
    public $PurchaseOrderNo;
    function getDocDate()
    {
        return $this->DocDate;
    }

    function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    function getDiscount()
    {
        return $this->Discount;
    }

    function getDocCode()
    {
        return $this->DocCode;
    }

    function getPurchaseOrderNo()
    {
        return $this->PurchaseOrderNo;
    }

    function setDocDate($DocDate)
    {
        $this->DocDate = $DocDate;
    }

    function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
    }

    function setDiscount($Discount)
    {
        $this->Discount = $Discount;
    }

    function setDocCode($DocCode)
    {
        $this->DocCode = $DocCode;
    }

    function setPurchaseOrderNo($PurchaseOrderNo)
    {
        $this->PurchaseOrderNo = $PurchaseOrderNo;
    }


    
}

