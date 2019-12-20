<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\CreateTransactionModel;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\InsuranceFactory;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;
use Shopware\Plugins\MoptAvalara\Util\BcMath;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
class GetTax extends AbstractService
{
    /**
     * @var string Item ID in Avalara response
     */
    const IMPORT_FEES_LINE = 'ImportFees';
    
    /**
     * @var string Item ID in Avalara response
     */
    const IMPORT_DUTIES_LINE = 'ImportDuties';

    /**
     * @var BcMath
     */
    protected $bcMath;

    /**
     * Array containing the user data
     * @var array
     */
    protected $userData;

    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
        $this->bcMath = new BcMath();
    }

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
        $taxLine = $this->getTaxLineForOrderBasketId($taxResult, $id);
        if (!$taxLine || !((float)$taxLine->taxableAmount)) {
            return 0;
        }
        /**
         * Recalculate a tax rate by subdividing tax amount with a total price
         * This solves a problem with a tax calculated only for a part of amount
         */
        $taxRate = $this->bcMath->bcdiv($taxLine->tax, $taxLine->lineAmount);
        
        return $this->bcMath->bcmul($taxRate, 100);
    }
    
    /**
     * get LandedCost from avalara response
     * @param \stdClass $taxResult
     * @return float
     */
    public function getLandedCost($taxResult)
    {
        $totalLandedCost = 0.0;
        if (!$this->isLandedCostEnabled()) {
            return $totalLandedCost;
        }
        $landedCostLineNumbers = [self::IMPORT_DUTIES_LINE, self::IMPORT_FEES_LINE];
        foreach ($taxResult->lines as $line) {
            if (in_array($line->lineNumber, $landedCostLineNumbers, false)) {
                $totalLandedCost = $this->bcMath->bcadd(
                    $totalLandedCost,
                    $this->bcMath->bcadd(
                        $line->lineAmount,
                        $line->tax
                    )
                );
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
                return $this->bcMath->bcadd($line->lineAmount, $line->tax);
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
                return $this->bcMath->bcadd($line->lineAmount, $line->tax);
            }
        }

        return 0.0;
    }

    /**
     * check if getTax call has to be made
     * @param CreateTransactionModel $model
     * @param \Enlight_Components_Session_Namespace $session
     * @return boolean
     */
    public function isGetTaxCallAvailable(CreateTransactionModel $model, \Enlight_Components_Session_Namespace $session)
    {
        if (!$this->isGetTaxEnabled()) {
            return false;
        }

        if ($this->isGetTaxDisabledForCountry()) {
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
     * @return bool
     */
    public function isGetTaxEnabled()
    {
        return $this
            ->getAdapter()
            ->getPluginConfig(Form::TAX_ENABLED_FIELD)
        ;
    }
    
    /**
     * @return bool
     */
    public function isLandedCostEnabled()
    {
        return $this
            ->getAdapter()
            ->getPluginConfig(Form::LANDEDCOST_ENABLED_FIELD)
        ;
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
        $data['discount'] = number_format($data['discount']);
        unset(
            $data['type'],
            $data['date'],
            $data['commit']
        );

        //Normalize floats
        foreach ($data['lines'] as $key => $line) {
            $data['lines'][$key]['amount'] = number_format($line['amount'], 2);
        }

        return md5(json_encode($data));
    }

    /**
     *
     * @param \stdClass | string $data
     * @return \stdClass
     * @throws \InvalidArgumentException
     */
    public function generateTaxResultFromResponse($data)
    {
        if (is_string($data) || !is_object($data)) {
            throw new \InvalidArgumentException($data);
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

    /**
     * Indicates if tax calculation is disabled for the users country
     * @return bool True, if tax calculation is disabled.
     */
    public function isGetTaxDisabledForCountry()
    {
        $restriction = $this
            ->getAdapter()
            ->getPluginConfig(Form::TAX_COUNTRY_RESTRICTION_FIELD)
        ;

        // No restriction set
        if (empty($restriction)) {
            return false;
        }

        // Restriction should be an array containing the related country ID's
        if (!is_array($restriction)) {
            return false;
        }

        // Fetch data for current user
        $userData = $this->getUserData();
        if (empty($userData)) {
            return false;
        }

        // Check if shipping country is set
        if (!isset($userData['additional']['countryShipping']['id'])) {
            return false;
        }

        return !in_array($userData['additional']['countryShipping']['id'], $restriction, false);
    }

    /**
     * Get all data from the current logged in user
     * @see sAdmin::sGetUserData()
     * @return array|false User data, of false if interrupted
     */
    protected function getUserData()
    {
        if ($this->userData !== null) {
            return $this->userData;
        }

        return $this->userData = Shopware()->Modules()->Admin()->sGetUserData();
    }
}
