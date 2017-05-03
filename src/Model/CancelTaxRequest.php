<?php

namespace Shopware\Plugins\MoptAvalara\Model;

/**
 * Description of CancelTaxRequest
 *
 */
class CancelTaxRequest extends AbstractModel
{
    public $CompanyCode;
    public $DocType;
    public $DocCode;
    public $CancelCode;
    
    public function getCompanyCode()
    {
        return $this->CompanyCode;
    }

    public function getDocType()
    {
        return $this->DocType;
    }

    public function getDocCode()
    {
        return $this->DocCode;
    }

    public function getCancelCode()
    {
        return $this->CancelCode;
    }

    /**
     * 
     * @param string $CompanyCode
     * @return CancelTaxRequest
     */
    public function setCompanyCode($CompanyCode)
    {
        $this->CompanyCode = $CompanyCode;
        return $this;
    }

    /**
     * 
     * @param string $DocType
     * @return CancelTaxRequest
     */
    public function setDocType($DocType)
    {
        $this->DocType = $DocType;
        return $this;
    }

    /**
     * 
     * @param string $DocCode
     * @return CancelTaxRequest
     */
    public function setDocCode($DocCode)
    {
        $this->DocCode = $DocCode;
        return $this;
    }

    /**
     * 
     * @param string $CancelCode
     * @return CancelTaxRequest
     */
    public function setCancelCode($CancelCode)
    {
        $this->CancelCode = $CancelCode;
        return $this;
    }
}
