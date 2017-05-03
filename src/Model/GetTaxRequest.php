<?php

namespace Shopware\Plugins\MoptAvalara\Model;

/**
 * Description of GetTaxRequest
 *
 */
class GetTaxRequest extends AbstractModel
{
    public $CustomerCode;
    public $DocDate;
    public $DocCode;
    public $DetailLevel;
    public $Commit;
    public $DocType;
    public $CustomerUsageType;
    public $BusinessIdentificationNo;
    public $ExemptionNo;
    public $Discount;
    /**
     *
     * @var TaxOverride
     */
    public $TaxOverride;
    public $PurchaseOrderNo;
    public $ReferenceCode;
    public $PosLaneCode;
    public $CurrencyCode;
    public $Addresses;
    public $Lines;
    
    public function getCustomerCode()
    {
        return $this->CustomerCode;
    }

    public function getDocDate()
    {
        return $this->DocDate;
    }

    public function getDocCode()
    {
        return $this->DocCode;
    }

    public function getDetailLevel()
    {
        return $this->DetailLevel;
    }

    public function getCommit()
    {
        return $this->Commit;
    }

    public function getDocType()
    {
        return $this->DocType;
    }

    public function getCustomerUsageType()
    {
        return $this->CustomerUsageType;
    }

    public function getBusinessIdentificationNo()
    {
        return $this->BusinessIdentificationNo;
    }

    public function getExemptionNo()
    {
        return $this->ExemptionNo;
    }

    public function getDiscount()
    {
        return $this->Discount;
    }

    public function getTaxOverride()
    {
        return $this->TaxOverride;
    }

    public function getPurchaseOrderNo()
    {
        return $this->PurchaseOrderNo;
    }

    public function getReferenceCode()
    {
        return $this->ReferenceCode;
    }

    public function getPosLaneCode()
    {
        return $this->PosLaneCode;
    }

    public function getCurrencyCode()
    {
        return $this->CurrencyCode;
    }

    public function getAddresses()
    {
        return $this->Addresses;
    }

    public function getLines()
    {
        return $this->Lines;
    }

    /**
     * 
     * @param string $CustomerCode
     * @return GetTaxRequest
     */
    public function setCustomerCode($CustomerCode)
    {
        $this->CustomerCode = $CustomerCode;
        return $this;
    }

    /**
     * 
     * @param string $DocDate
     * @return GetTaxRequest
     */
    public function setDocDate($DocDate)
    {
        $this->DocDate = $DocDate;
        return $this;
    }

    /**
     * 
     * @param string $DocCode
     * @return GetTaxRequest
     */
    public function setDocCode($DocCode)
    {
        $this->DocCode = $DocCode;
        return $this;
    }

    /**
     * 
     * @param string $DetailLevel
     * @return GetTaxRequest
     */
    public function setDetailLevel($DetailLevel)
    {
        $this->DetailLevel = $DetailLevel;
        return $this;
    }

    /**
     * 
     * @param string $Commit
     * @return GetTaxRequest
     */
    public function setCommit($Commit)
    {
        $this->Commit = $Commit;
        return $this;
    }

    /**
     * 
     * @param string $DocType
     * @return GetTaxRequest
     */
    public function setDocType($DocType)
    {
        $this->DocType = $DocType;
        return $this;
    }

    /**
     * 
     * @param string $CustomerUsageType
     * @return GetTaxRequest
     */
    public function setCustomerUsageType($CustomerUsageType)
    {
        $this->CustomerUsageType = $CustomerUsageType;
        return $this;
    }

    /**
     * 
     * @param string $BusinessIdentificationNo
     * @return GetTaxRequest
     */
    public function setBusinessIdentificationNo($BusinessIdentificationNo)
    {
        $this->BusinessIdentificationNo = $BusinessIdentificationNo;
        return $this;
    }

    /**
     * 
     * @param string $ExemptionNo
     * @return GetTaxRequest
     */
    public function setExemptionNo($ExemptionNo)
    {
        $this->ExemptionNo = $ExemptionNo;
        return $this;
    }

    /**
     * 
     * @param string $Discount
     * @return GetTaxRequest
     */
    public function setDiscount($Discount)
    {
        $this->Discount = $Discount;
        return $this;
    }

    /**
     * 
     * @param string $TaxOverride
     * @return GetTaxRequest
     */
    public function setTaxOverride(TaxOverride $TaxOverride)
    {
        $this->TaxOverride = $TaxOverride;
        return $this;
    }

    /**
     * 
     * @param string $PurchaseOrderNo
     * @return GetTaxRequest
     */
    public function setPurchaseOrderNo($PurchaseOrderNo)
    {
        $this->PurchaseOrderNo = $PurchaseOrderNo;
        return $this;
    }

    /**
     * 
     * @param string $ReferenceCode
     * @return GetTaxRequest
     */
    public function setReferenceCode($ReferenceCode)
    {
        $this->ReferenceCode = $ReferenceCode;
        return $this;
    }

    /**
     * 
     * @param string $PosLaneCode
     * @return GetTaxRequest
     */
    public function setPosLaneCode($PosLaneCode)
    {
        $this->PosLaneCode = $PosLaneCode;
        return $this;
    }

    /**
     * 
     * @param string $CurrencyCode
     * @return GetTaxRequest
     */
    public function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
        return $this;
    }

    /**
     * 
     * @param string $Addresses
     * @return GetTaxRequest
     */
    public function setAddresses($Addresses)
    {
        $this->Addresses = $Addresses;
        return $this;
    }

    /**
     * 
     * @param string $Lines
     * @return GetTaxRequest
     */
    public function setLines($Lines)
    {
        $this->Lines = $Lines;
        
        return $this;
    }
}
