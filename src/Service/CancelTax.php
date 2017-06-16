<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\VoidTransactionModel;
use Avalara\VoidReasonCode;
use Shopware\Plugins\MoptAvalara\Form\PluginConfigForm;

/**
 * Description of CancelTax
 *
 */
class CancelTax extends AbstractService
{
    /**
     *
     * @param string $docCode
     * @return \stdClass
     */
    public function cancel($docCode)
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        $companyCode = $this->getAdapter()->getPluginConfig(PluginConfigForm::COMPANY_CODE_FIELD);
        
        $model = new VoidTransactionModel();
        $model->code = VoidReasonCode::C_DOCVOIDED;
        $response = $client->voidTransaction($companyCode, $docCode, $model);
        
        return $response;
    }
}
