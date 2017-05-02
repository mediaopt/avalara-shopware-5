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

    public function setCompanyCode($CompanyCode)
    {
        $this->CompanyCode = $CompanyCode;
    }

    public function setDocType($DocType)
    {
        $this->DocType = $DocType;
    }

    public function setDocCode($DocCode)
    {
        $this->DocCode = $DocCode;
    }

    public function setCancelCode($CancelCode)
    {
        $this->CancelCode = $CancelCode;
    }
}
