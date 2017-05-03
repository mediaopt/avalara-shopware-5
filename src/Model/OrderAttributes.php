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

    /**
     * 
     * @param string $DocDate
     * @return OrderAttributes
     */
    function setDocDate($DocDate)
    {
        $this->DocDate = $DocDate;
        return $this;
    }

    /**
     * 
     * @param string $CurrencyCode
     * @return OrderAttributes
     */
    function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
        return $this;
    }

    /**
     * 
     * @param string $Discount
     * @return OrderAttributes
     */
    function setDiscount($Discount)
    {
        $this->Discount = $Discount;
        return $this;
    }

    /**
     * 
     * @param string $DocCode
     * @return OrderAttributes
     */
    function setDocCode($DocCode)
    {
        $this->DocCode = $DocCode;
        return $this;
    }

    /**
     * 
     * @param string $PurchaseOrderNo
     * @return OrderAttributes
     */
    function setPurchaseOrderNo($PurchaseOrderNo)
    {
        $this->PurchaseOrderNo = $PurchaseOrderNo;
        return $this;
    }
}

