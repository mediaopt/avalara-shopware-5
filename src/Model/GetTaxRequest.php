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
    public $CompanyCode;
    public $Client;
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

    public function getCompanyCode()
    {
        return $this->CompanyCode;
    }

    public function getClient()
    {
        return $this->Client;
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

    public function setCustomerCode($CustomerCode)
    {
        $this->CustomerCode = $CustomerCode;
    }

    public function setDocDate($DocDate)
    {
        $this->DocDate = $DocDate;
    }

    public function setCompanyCode($CompanyCode)
    {
        $this->CompanyCode = $CompanyCode;
    }

    public function setClient($Client)
    {
        $this->Client = $Client;
    }

    public function setDocCode($DocCode)
    {
        $this->DocCode = $DocCode;
    }

    public function setDetailLevel($DetailLevel)
    {
        $this->DetailLevel = $DetailLevel;
    }

    public function setCommit($Commit)
    {
        $this->Commit = $Commit;
    }

    public function setDocType($DocType)
    {
        $this->DocType = $DocType;
    }

    public function setCustomerUsageType($CustomerUsageType)
    {
        $this->CustomerUsageType = $CustomerUsageType;
    }

    public function setBusinessIdentificationNo($BusinessIdentificationNo)
    {
        $this->BusinessIdentificationNo = $BusinessIdentificationNo;
    }

    public function setExemptionNo($ExemptionNo)
    {
        $this->ExemptionNo = $ExemptionNo;
    }

    public function setDiscount($Discount)
    {
        $this->Discount = $Discount;
    }

    public function setTaxOverride(TaxOverride $TaxOverride)
    {
        $this->TaxOverride = $TaxOverride;
    }

    public function setPurchaseOrderNo($PurchaseOrderNo)
    {
        $this->PurchaseOrderNo = $PurchaseOrderNo;
    }

    public function setReferenceCode($ReferenceCode)
    {
        $this->ReferenceCode = $ReferenceCode;
    }

    public function setPosLaneCode($PosLaneCode)
    {
        $this->PosLaneCode = $PosLaneCode;
    }

    public function setCurrencyCode($CurrencyCode)
    {
        $this->CurrencyCode = $CurrencyCode;
    }

    public function setAddresses($Addresses)
    {
        $this->Addresses = $Addresses;
    }

    public function setLines($Lines)
    {
        $this->Lines = $Lines;
    }
}
