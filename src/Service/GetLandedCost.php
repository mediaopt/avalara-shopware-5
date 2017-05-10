<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\CreateTransactionModel;

/**
 * Description of GetLandedCost
 *
 */
class GetLandedCost extends AbstractService
{
    /**
     * 
     * @param \Avalara\CreateTransactionModel $model
     * @return \stdClass
     */
    public function calculate(CreateTransactionModel $model)
    {
        $client = $this->getAdapter()->getAvaTaxClient();

        //return $response;
    }
}
