<?php

namespace Shopware\Plugins\MoptAvalara\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\LineFactory;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;

/**
 * Factory to create CreateTransactionModel from the bucket
 *
 */
class TransactionModelFactory extends AbstractFactory
{
    /**
     * Total discount
     * @var float
     */
    protected $discount = 0.0;

    /**
     * 
     * @param string $docType
     * @param bool $isCommit
     * @return \Avalara\CreateTransactionModel
     */
    public function build($docType, $isCommit = false)
    {
        $user = $this->getUserData();

        $model = new CreateTransactionModel();
        $model->businessIdentificationNo = $user['billingaddress']['ustid'];
        $model->commit = $isCommit;
        $model->customerCode = $user['additional']['user']['id'];
        $model->date = date('Y-m-d', time());
        $model->discount = $this->discount;
        $model->type = $docType;
        $model->currencyCode = Shopware()->Shop()->getCurrency()->getCurrency();
        $model->addresses = $this->getAddressesModel();
        $model->lines = $this->getLineModels();
        $model->companyCode = $this
            ->getAdapter()
            ->getPluginConfig(FormCreator::COMPANY_CODE_FIELD)
        ;

        if (!empty($user['additional']['user']['mopt_avalara_exemption_code'])) {
            $model->exemptionNo = $user['additional']['user']['mopt_avalara_exemption_code'];
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
        $addressFactory = $this->getAdapter()->getFactory('AddressFactory');

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress();
        
        return $addressesModel;
    }
    
    /**
     * 
     * @return LineItemModel[]
     */
    protected function getLineModels()
    {
        /* @var $lineFactory LineFactory */
        $lineFactory = $this->getAdapter()->getFactory('LineFactory');
        $lines = [];
        $positions = Shopware()->Modules()->Basket()->sGetBasket();
        
        foreach ($positions['content'] as $position) {
            if (!LineFactory::isDiscount($position['modus'])) {
                $lines[] = $lineFactory->build($position);
                continue;
            }
            
            if (LineFactory::isDiscountGlobal($position)) {
                $this->discount -= floatval($position['netprice']);
                continue;
            }
            
            $position['id'] = LineFactory::ARTICLEID__VOUCHER;
            $lines[] = $lineFactory->build($position);
        }

        if ($shipment = $this->getShippingCharges()) {
            $lines[] = $lineFactory->build($shipment);
        }
        
        return $lines;
    }

    /**
     * get shipment information
     *
     * @return array
     */
    protected function getShippingCharges()
    {
        if (empty(Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsWithTax'])) {
            return null;
        }
        
        //create shipping item for compatibility reasons with line data
        $shippingItem = [];
        $shippingItem['id'] = LineFactory::ARTICLEID__SHIPPING;
        $shippingItem['ean'] = '';
        $shippingItem['quantity'] = 1;
        //set grossprice as net => shipping will be transmitted as taxincluded = yes
        $shippingItem['netprice'] = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsNet'];
        $shippingItem['brutprice'] = Shopware()->Session()->sOrderVariables['sBasket']['sShippingcostsWithTax'];
        $shippingItem['articlename'] = 'Shipping';
        $shippingItem['articleID'] = 0;
        $shippingItem['dispatchID'] = Shopware()->Session()->sOrderVariables['sDispatch']['id'];
        
        return $shippingItem;
    }
}
