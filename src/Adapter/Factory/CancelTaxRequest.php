<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

/**
 * Description of CancelTaxRequest
 *
 */
class CancelTaxRequest extends AbstractFactory
{
    public function build($docCode, $cancelCode)
    {
        $model = new \Shopware\Plugins\MoptAvalara\Model\CancelTaxRequest();
        $model
            ->setCancelCode($cancelCode)
            ->setDocCode($docCode)
            ->setDocType(\Shopware\Plugins\MoptAvalara\Model\DocumentType::SALES_INVOICE)
        ;
        
        return $model;
    }
}
