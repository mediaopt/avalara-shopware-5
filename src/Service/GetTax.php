<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\CreateTransactionModel;
use Avalara\DocumentType;

/**
 * Description of GetTax
 *
 */
class GetTax extends AbstractService
{
    /**
     * 
     * @param \Avalara\CreateTransactionModel $model
     * @return \stdClass
     */
    public function calculate(CreateTransactionModel $model)
    {
        $client = $this->getAdapter()->getClient();
        $response = $client->createTransaction(null, $model);

        return $response;
    }
}
