<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\CreateTransactionModel;
use Avalara\AdjustTransactionModel;
use Avalara\AdjustmentReason;
use Shopware\Plugins\MoptAvalara\Form\PluginConfigForm;

/**
 * Description of GetTax
 *
 */
class AdjustTransaction extends AbstractService
{
    const UPDATE_DESCRIPTION = 'Order updated.';
    
    /**
     *
     * @param \Avalara\CreateTransactionModel $model
     * @param string $docCode
     * @return \stdClass
     */
    public function adjustTransaction(CreateTransactionModel $model, $docCode)
    {
        $companyCode = $this->getAdapter()->getPluginConfig(PluginConfigForm::COMPANY_CODE_FIELD);
        $client = $this->getAdapter()->getAvaTaxClient();
        
        $adjustModel = new AdjustTransactionModel();
        $adjustModel->adjustmentReason = AdjustmentReason::C_OTHER;
        $adjustModel->adjustmentDescription = self::UPDATE_DESCRIPTION;
        $adjustModel->newTransaction = $model;
        $response = $client->adjustTransaction($companyCode, $docCode, $adjustModel);

        return $response;
    }
}
