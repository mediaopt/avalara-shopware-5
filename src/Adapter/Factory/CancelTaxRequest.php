<?php

namespace Mediaopt\Avalara\Adapter\Factory;

/**
 * Description of CancelTaxRequest
 *
 */
class CancelTaxRequest extends AbstractFactory
{
    public function build($docCode, $cancelCode)
    {
        $model = new \Mediaopt\Avalara\Sdk\Model\CancelTaxRequest();
        $model->setCancelCode($cancelCode);
        $model->setCompanyCode($this->getPluginConfig()->mopt_avalara__company_code);
        $model->setDocCode($docCode);
        $model->setDocType(\Mediaopt\Avalara\Sdk\Model\DocumentType::SALES_INVOICE);
        
        return $model;
    }
}
