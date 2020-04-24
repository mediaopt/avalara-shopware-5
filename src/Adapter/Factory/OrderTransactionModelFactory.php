<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Avalara\LineItemModel;
use Avalara\DocumentType;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;

/**
 *
 * Factory to create CreateTransactionModel from the basket.
 * Just to get estimated tax and landed cost
 * Without commiting it to Avalara
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
 */
class OrderTransactionModelFactory extends AbstractTransactionModelFactory
{
    /**
     *
     * @return \Avalara\CreateTransactionModel
     */
    public function build()
    {
        $user = $this->getUserData();

        $model = new CreateTransactionModel();
        $model->businessIdentificationNo = $user['billingaddress']['ustid'];
        $model->commit = false;
        $model->customerCode = $user['additional']['user']['id'];
        $model->date = date(DATE_W3C);
        $model->discount = $this->getDiscount();
        $model->type = DocumentType::C_SALESORDER;
        $model->currencyCode = Shopware()->Shop()->getCurrency()->getCurrency();
        $model->addresses = $this->getAddressesModel();
        $model->lines = $this->getLineModels();
        $model->companyCode = $this->getCompanyCode();
        $model->parameters = $this->getTransactionParameters();

        if (!empty($user['additional']['user']['mopt_avalara_exemption_code'])) {
            $model->customerUsageType = $user['additional']['user']['mopt_avalara_exemption_code'];
        }

        return $model;
    }

    /**
     *
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel()
    {
        /* @var $addressFactory AddressFactory */
        $addressFactory = $this->getAddressFactory();

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress();
        
        return $addressesModel;
    }
    
    /**
     * @param array $positions
     * @return LineItemModel[]
     */
    protected function getLineModels($positions = [])
    {
        $basketPositions = Shopware()->Modules()->Basket()->sGetBasket();

        return parent::getLineModels($basketPositions['content']);
    }
    
    /**
     * Discount amount from a voucher is a negative value!
     * @return float
     */
    protected function getDiscount()
    {
        $positions = Shopware()->Modules()->Basket()->sGetBasket();
        $discount = 0.0;
        
        foreach ($positions['content'] as $position) {
            if (!LineFactory::isDiscount($position['modus'])) {
                continue;
            }
            
            if (LineFactory::isNotVoucher($position)) {
                $discount -= (float)$position['netprice'];
            }
        }

        return $discount;
    }

    /**
     *
     * @return int|null
     */
    protected function getShippingId()
    {
        if (!$orderVars = $this->getOrderVariables()) {
            return null;
        }
        
        return (isset($orderVars['sDispatch']) && isset($orderVars['sDispatch']['id']))
            ? $orderVars['sDispatch']['id']
            : null
        ;
    }
    
    /**
     *
     * @return float
     */
    protected function getShippingPrice()
    {
        if (!$orderVars = $this->getOrderVariables()) {
            return null;
        }

        return Shopware()->Session()->moptAvalaraShippingcostsNetOrigin
            ?: $orderVars['sBasket']['sShippingcostsNet']
        ;
    }
    
    /**
     *
     * @return array
     */
    protected function getOrderVariables()
    {
        return Shopware()->Session()->sOrderVariables;
    }

    /**
     * @return string | null
     * @throws \InvalidArgumentException
     */
    protected function getIncoterm()
    {
        $user = $this->getUserData();
        if (!$countryId = $user['additional']['countryShipping']['id']) {
            return null;
        }
        
        $addressFactory = $this->getAddressFactory();
        if (!$country = $addressFactory->getDeliveryCountry($countryId)) {
            return null;
        }
        
        if (!$attr = $country->getAttribute()) {
            return null;
        }
        
        $incoterms = $attr->getMoptAvalaraIncoterms();
        if (!$incoterms || Form::INCOTERMS_DEFAULT === $incoterms) {
            return null;
        }
        
        return $incoterms;
    }
}
