<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\CreateTransactionModel;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\InsuranceFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;

/**
 * Description of GetTax
 *
 */
class GetTax extends AbstractService
{
    const IMPORT_FEES_LINE = 'ImportFees';
    const IMPORT_DUTIES_LINE = 'ImportDuties';
    
    /**
     *
     * @param CreateTransactionModel $model
     * @return \stdClass
     */
    public function calculate(CreateTransactionModel $model)
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        return $client->createTransaction(null, $model);
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
        $taxRate = ((float)$taxLine->tax / (float)$taxLine->taxableAmount) * 100;
        
        return number_format($taxRate, 2);
    }
    
    /**
     * get LandedCost from avalara response
     * @param \stdClass $taxResult
     * @return float
     */
    public function getLandedCost($taxResult)
    {
        $totalLandedCost = 0.0;
        $landedCostLineNumbers = [self::IMPORT_DUTIES_LINE, self::IMPORT_FEES_LINE];
        foreach ($taxResult->lines as $line) {
            if (in_array($line->lineNumber, $landedCostLineNumbers)) {
                $totalLandedCost += ($line->lineAmount + $line->tax);
            }
        }

        return $totalLandedCost;
    }
    
    /**
     * Get insurance cost  from avalara response
     * @param \stdClass $taxResult
     * @return float
     */
    public function getInsuranceCost($taxResult)
    {
        foreach ($taxResult->lines as $line) {
            if (InsuranceFactory::ARTICLE_ID === $line->lineNumber) {
                return $line->lineAmount + $line->tax;
            }
        }

        return 0.0;
    }
    /**
     * Get shipping cost from avalara response
     * @param \stdClass $taxResult
     * @return float
     */
    public function getShippingCost($taxResult)
    {
        foreach ($taxResult->lines as $line) {
            if (ShippingFactory::ARTICLE_ID === $line->lineNumber) {
                return $line->lineAmount + $line->tax;
            }
        }

        return 0.0;
    }
    
    /**
     * check if getTax call has to be made
     * @param \Avalara\CreateTransactionModel $model
     * @param 
     * @return boolean
     * @todo: check country (?)
     */
    public function isGetTaxCallAvalible(CreateTransactionModel $model, \Enlight_Components_Session_Namespace $session)
    {
        $taxEnabled = $this
            ->getAdapter()
            ->getPluginConfig(Form::TAX_ENABLED_FIELD)
        ;
        if (!$taxEnabled) {
            return false;
        }

        if (!$session->MoptAvalaraGetTaxResult || !$session->MoptAvalaraGetTaxRequestHash) {
            return true;
        }

        if ($session->MoptAvalaraGetTaxRequestHash !== $this->getHashFromRequest($model)) {
            return true;
        }

        return false;
    }
    
    /**
     * get hash from request to compare calculate & commit call
     * unset changing fields during both calls
     *
     * @param \Avalara\CreateTransactionModel $model
     * @return string
     */
    public function getHashFromRequest(CreateTransactionModel $model)
    {
        $data = $this->objectToArray($model);
        $data['discount'] = number_format($data['discount'], 0);
        unset($data['type']);
        unset($data['date']);
        unset($data['commit']);
        
        //Normalize floats
        foreach ($data['lines'] as $key => $line) {
            $data['lines'][$key]['amount'] = number_format($line['amount'], 0);
        }

        return md5(json_encode($data));
    }
    
    /**
     *
     * @param \stdClass | string $data
     * @return \stdClass
     */
    public function generateTaxResultFromResponse($data)
    {
        if (is_string($data) || !is_object($data)) {
            throw new \Exception($data);
        }
        $result = new \stdClass();
        $result->totalTaxable = $data->totalTaxable;
        $result->totalTax = $data->totalTax;
        $result->lines = $data->lines;
        
        return $result;
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

    /**
     *
     * @param \stdClass $object
     * @return array
     */
    protected function objectToArray($object)
    {
        $data = (array)$object;
        foreach ($data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $data[$key] = $this->objectToArray($value);
            }
        }
        
        return $data;
    }
}
