<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\VoidTransactionModel;
use Avalara\VoidReasonCode;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;

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
    public function cancel($docCode = null)
    {
        if (empty($docCode)) {
            throw new \Exception('Cannot cancel transaction with empty DocCode');
        }
        $client = $this->getAdapter()->getAvaTaxClient();
        $companyCode = $this->getAdapter()->getPluginConfig(Form::COMPANY_CODE_FIELD);
        
        $model = new VoidTransactionModel();
        $model->code = VoidReasonCode::C_DOCVOIDED;
        $response = $client->voidTransaction($companyCode, $docCode, $model);
        $this
            ->getAdapter()
            ->getLogger()
            ->info('Order with docCode: ' . $docCode . ' has been voided');
        
        return $response;
    }
}
