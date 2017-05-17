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
     * @param CreateTransactionModel $model
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
     * @param \stdClass $taxResult
     * @param string|int $id
     * @return float
     */
    public function getTaxForOrderBasketId($taxResult, $id)
    {
        if (!$taxLineInformation = $this->getTaxLineForOrderBasketId($taxResult, $id)) {
            return 0;
        }

        return (float)$taxLineInformation->tax;
    }
    
    /**
     * get tax rate from avalara response
     * @param \stdClass $taxResult
     * @param string|int $id
     * @return float
     */
    public function getTaxRateForOrderBasketId($taxResult, $id)
    {
        if (!$taxLine = $this->getTaxLineForOrderBasketId($taxResult, $id)) {
            return 0;
        }

        return ((float)$taxLine->tax / (float)$taxLine->taxableAmount) * 100;
    }

    /**
     * get tax line info from avalara response
     * @param \stdClass $taxResult
     * @param string|int $id
     * @return \stdClass | null
     */
    private function getTaxLineForOrderBasketId($taxResult, $id)
    {
        foreach ($taxResult->lines as $taxLine) {
            if ($id == $taxLine->lineNumber && $taxLine->tax) {
                return $taxLine;
            }
        }

        return null;
    }
}
