<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\VoidTransactionModel;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;

/**
 * Description of CancelTax
 *
 */
class CancelTax extends AbstractService
{
    /**
     *
     * @param string $docCode
     * @param string $cancelCode
     * @return \stdClass
     */
    public function cancel($docCode, $cancelCode)
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        $companyCode = $this->getAdapter()->getPluginConfig(FormCreator::COMPANY_CODE_FIELD);
        
        $model = new VoidTransactionModel();
        $model->code = $cancelCode;
        $response = $client->voidTransaction($companyCode, $docCode, $model);
        
        return $response;
    }
}
