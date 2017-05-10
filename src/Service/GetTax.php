<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\CreateTransactionModel;

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
        $client = $this->getAdapter()->getAvaTaxClient();
        $response = $client->createTransaction(null, $model);

        return $response;
    }
    
    /**
     * get tax ammount from avalara response
     * @param string|int $id
     * @param \stdClass $taxInformation
     * @return float
     */
    public function getTaxForOrderBasketId($id, $taxInformation)
    {
        if (!$taxLineInformation = $this->getTaxLineForOrderBasketId($id, $taxInformation)) {
            return 0;
        }

        return (float)$taxLineInformation->tax;
    }
    
    /**
     * get tax rate from avalara response
     * @param string|int $id
     * @param \stdClass $taxInformation
     * @return float
     */
    public function getTaxRateForOrderBasketId($id, $taxInformation)
    {
        if (!$taxLine = $this->getTaxLineForOrderBasketId($id, $taxInformation)) {
            return 0;
        }

        return ((float)$taxLine->tax / (float)$taxLine->taxableAmount) * 100;
    }

    /**
     * get tax line info from avalara response
     * @param string|int $id
     * @param \stdClass $taxInformation
     * @return \stdClass
     */
    private function getTaxLineForOrderBasketId($id, $taxInformation)
    {
        foreach ($taxInformation->lines as $taxLine) {
            if ($id == $taxLine->lineNumber && $taxLine->tax) {
                return $taxLine;
            }
        }

        return null;
    }
}
