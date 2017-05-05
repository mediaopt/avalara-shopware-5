<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\CreateTransactionModel;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;

/**
 * Description of GetTax
 *
 */
class AdjustTransaction extends AbstractService
{
    /**
     * 
     * @param \Avalara\CreateTransactionModel $model
     * @param string $docCode
     * @return \stdClass
     */
    public function adjustTransaction(CreateTransactionModel $model, $docCode)
    {
        $companyCode = $this->getAdapter()->getPluginConfig(FormCreator::COMPANY_CODE_FIELD);
        $client = $this->getAdapter()->getClient();
        $response = $client->adjustTransaction($companyCode, $docCode, $model);

        return $response;
    }
}
