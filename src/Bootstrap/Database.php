<?php

namespace Shopware\Plugins\MoptAvalara\Bootstrap;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;

/**
 * This class will update attributes in DB
 *
 * @author bubnov
 */
class Database
{
    /**
     * Tables
     */
    const CATEGORIES_ATTR_TABLE = 's_categories_attributes';
    const ARTICLES_ATTR_TABLE = 's_articles_attributes';
    const USER_ATTR_TABLE = 's_user_attributes';
    const ORDER_ATTR_TABLE = 's_order_attributes';
    const VOUCHER_ATTR_TABLE = 's_emarketing_vouchers_attributes';
    const DISPATCH_ATTR_TABLE = 's_premium_dispatch_attributes';
    const COUNTRIES_ATTR_TABLE = 's_core_countries_attributes';
    
    /**
     * Attributes
     */
    const TAXCODE_FIELD = 'mopt_avalara_taxcode';
    const HSCODE_FIELD = 'mopt_avalara_hscode';
    const DOC_CODE_FIELD = 'mopt_avalara_doc_code';
    const EXEMPTION_CODE_FIELD = 'mopt_avalara_exemption_code';
    const ORDER_CHANGED_FIELD = 'mopt_avalara_order_changed';
    const INSURED_FIELD = 'mopt_avalara_insured';
    const EXPRESS_SHIPPING_FIELD = 'mopt_avalara_express_shipping';
    const INCOTERMS_FIELD = 'mopt_avalara_incoterms';
    const LANDEDCOST_FIELD = 'mopt_avalara_landedcost';
    const TRANSACTION_TYPE_FIELD = 'mopt_avalara_transaction_type';
    
    /**
     *
     * @var CrudService
     */
    private $crudService;
    
    /**
     *
     * @var ModelManager
     */
    private $modelManager;
    
    /**
     * 
     * @param CrudService $crudService
     * @param ModelManager $modelManager
     */
    public function __construct(CrudService $crudService, ModelManager $modelManager)
    {
        $this->crudService = $crudService;
        $this->modelManager = $modelManager;
    }
    
    /**
     * Extends attributes with DHL properties
     * @return \Shopware\Plugins\MoptAvalara\Bootstrap\Database
     * @throws \Exception
     */
    public function install()
    {
        $this->addStringField(
            self::CATEGORIES_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'Avalara Tax Code',
            'This is the Avalara Tax code of the category sent to Avalara.'
        );
        
        $this->addStringField(
            self::CATEGORIES_ATTR_TABLE,
            self::HSCODE_FIELD,
            'Avalara Harmonized Classification Code (hsCode)',
            'This is the Avalara Harmonized Classification Code (hsCode) of the article sent to Avalara.'
        );
        
        $this->addStringField(
            self::ARTICLES_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'Avalara Tax Code',
            'This is the Avalara Tax code of the article sent to Avalara.'
        );
        
        $this->addStringField(
            self::ARTICLES_ATTR_TABLE,
            self::HSCODE_FIELD,
            'Avalara Harmonized Classification Code (hsCode)',
            'This is the Avalara Harmonized Classification Code (hsCode) of the article sent to Avalara.'
        );
        
        $this->addStringField(
            self::USER_ATTR_TABLE,
            self::EXEMPTION_CODE_FIELD,
            'Avalara Exemption Code',
            'Here is the exemption code for a use that can be tax-free for you.'
        );
        
        $this->addStringField(
            self::ORDER_ATTR_TABLE,
            self::DOC_CODE_FIELD,
            null, null, null,
            false
        );
        
        $this->addStringField(
            self::ORDER_ATTR_TABLE,
            self::TRANSACTION_TYPE_FIELD,
            null, null, null,
            false
        );
        
        $this->addBoolField(
            self::ORDER_ATTR_TABLE,
            self::ORDER_CHANGED_FIELD, 
            null, null, null, 
            false
        );
        
        $this->addStringField(
            self::ORDER_ATTR_TABLE,
            self::LANDEDCOST_FIELD, 
            null, null, null, 
            false
        );
        
        $this->addStringField(
            self::ORDER_ATTR_TABLE,
            self::INCOTERMS_FIELD,
            null, null, null, 
            false
        );
        
        $this->addBoolField(
            self::ORDER_ATTR_TABLE,
            self::INSURED_FIELD,
            null, null, null, 
            false
        );
        
        $this->addBoolField(
            self::ORDER_ATTR_TABLE,
            self::EXPRESS_SHIPPING_FIELD,
            null, null, null, 
            false
        );
        
        $this->addStringField(
            self::VOUCHER_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'Avalara Tax Code',
            'This is where the Avalara Tax code for vouchers is given, which is sent to Avalara.'
        );
        
        $this->addStringField(
            self::DISPATCH_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'Avalara Tax Code',
            'This is the Avalara Tax code for the shipment sent to Avalara. Leave empty to use default.',
            ShippingFactory::TAXCODE
        );
        
        $this->addBoolField(
            self::DISPATCH_ATTR_TABLE,
            self::INSURED_FIELD,
            'Insurance 100%',
            'You can set if a delivery is completely insured.'
        );
        
        $this->addBoolField(
            self::DISPATCH_ATTR_TABLE,
            self::EXPRESS_SHIPPING_FIELD,
            'Express delivery',
            'You can set if this is an express delivery.'
        );

        $this->crudService->update(
            self::COUNTRIES_ATTR_TABLE,
            self::INCOTERMS_FIELD,
            'combobox',
            [
                'label' => 'Incoterms for Landed cost',
                'supportText' => 'Terms of sale. Used to determine buyer obligations for a landed cost.',
                'helpText' => '',
                'translatable' => false,
                'displayInBackend' => true,
                'position' => 10,
                'custom' => true,
                'defaultValue' => Form::INCOTERMS_DEFAULT,
                'arrayStore' => [
                    [
                        'key' => Form::INCOTERMS_DDP,
                        'value' => Form::INCOTERMS_DDP_LABEL
                    ],
                    [
                        'key' => Form::INCOTERMS_DAP,
                        'value' => Form::INCOTERMS_DAP_LABEL
                    ],
                    [
                        'key' => Form::INCOTERMS_DEFAULT,
                        'value' => Form::INCOTERMS_DEFAULT_LABEL
                    ],
                ],
            ],
            null,
            false,
            Form::INCOTERMS_DEFAULT
        );

        $this->refreshAttributeModels([
            self::DISPATCH_ATTR_TABLE,
            self::CATEGORIES_ATTR_TABLE,
            self::ARTICLES_ATTR_TABLE,
            self::USER_ATTR_TABLE,
            self::ORDER_ATTR_TABLE,
            self::VOUCHER_ATTR_TABLE,
            self::COUNTRIES_ATTR_TABLE,
        ]);
        
        return $this;
    }

    /**
     * 
     * @param string $table
     * @param string $name
     * @param string $label
     * @param string $descr
     * @param string $default
     * @param bool $displayInBackend
     */
    private function addStringField($table, $name, $label = '', $descr = '', $default = null, $displayInBackend = true)
    {
        $this->crudService->update(
            $table,
            $name,
            'string',
            [
                'label' => $label,
                'supportText' => $descr,
                'helpText' => '',
                'translatable' => false,
                'displayInBackend' => $displayInBackend,
                'position' => 10,
                'defaultValue' => $default,
                'custom' => true,
            ],
            null,
            false,
            $default
        );
    }
    /**
     * 
     * @param string $table
     * @param string $name
     * @param string $label
     * @param string $descr
     * @param string $default
     * @param bool $displayInBackend
     */
    private function addBoolField($table, $name, $label = '', $descr = '', $default = null, $displayInBackend = true)
    {
        $this->crudService->update(
            $table,
            $name,
            'boolean',
            [
                'label' => $label,
                'supportText' => $descr,
                'helpText' => '',
                'translatable' => false,
                'displayInBackend' => $displayInBackend,
                'position' => 10,
                'defaultValue' => $default,
                'custom' => true,
            ],
            null,
            false,
            $default
        );
    }

    /**
     *
     * @param array $tables
     */
    private function refreshAttributeModels($tables = [])
    {
        if (empty($tables)) {
            return;
        }
        
        $this->modelManager
            ->getConfiguration()
            ->getMetadataCacheImpl()
            ->deleteAll()
        ;
        $this->modelManager->generateAttributeModels($tables);
    }
}
