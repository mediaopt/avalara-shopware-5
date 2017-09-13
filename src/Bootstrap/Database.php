<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Bootstrap;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;

/**
 * This class will update attributes in DB
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Bootstrap
 */
class Database
{
    /**
     * @var string Table to be updated
     */
    const CATEGORIES_ATTR_TABLE = 's_categories_attributes';
    
    /**
     * @var string Table to be updated
     */
    const ARTICLES_ATTR_TABLE = 's_articles_attributes';
    
    /**
     * @var string Table to be updated
     */
    const USER_ATTR_TABLE = 's_user_attributes';
    
    /**
     * @var string Table to be updated
     */
    const ORDER_ATTR_TABLE = 's_order_attributes';
    
    /**
     * @var string Table to be updated
     */
    const VOUCHER_ATTR_TABLE = 's_emarketing_vouchers_attributes';
    
    /**
     * @var string Table to be updated
     */
    const DISPATCH_ATTR_TABLE = 's_premium_dispatch_attributes';
    
    /**
     * @var string Table to be updated
     */
    const COUNTRIES_ATTR_TABLE = 's_core_countries_attributes';
    
    /**
     * @var string Attribute to be created
     */
    const TAXCODE_FIELD = 'mopt_avalara_taxcode';
    
    /**
     * @var string Attribute to be created
     */
    const HSCODE_FIELD = 'mopt_avalara_hscode';
    
    /**
     * @var string Attribute to be created
     */
    const DOC_CODE_FIELD = 'mopt_avalara_doc_code';
    
    /**
     * @var string Attribute to be created
     */
    const EXEMPTION_CODE_FIELD = 'mopt_avalara_exemption_code';
    
    /**
     * @var string Attribute to be created
     */
    const ORDER_CHANGED_FIELD = 'mopt_avalara_order_changed';
    
    /**
     * @var string Attribute to be created
     */
    const INSURED_FIELD = 'mopt_avalara_insured';
    
    /**
     * @var string Attribute to be created
     */
    const EXPRESS_SHIPPING_FIELD = 'mopt_avalara_express_shipping';
    
    /**
     * @var string Attribute to be created
     */
    const INCOTERMS_FIELD = 'mopt_avalara_incoterms';
    
    /**
     * @var string Attribute to be created
     */
    const LANDEDCOST_FIELD = 'mopt_avalara_landedcost';
    
    /**
     * @var string Attribute to be created
     */
    const INSURANCE_FIELD = 'mopt_avalara_insurance';
    
    /**
     * @var string Attribute to be created
     */
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
     * @param CrudService $crudService
     * @param ModelManager $modelManager
     */
    public function __construct(CrudService $crudService, ModelManager $modelManager)
    {
        $this->crudService = $crudService;
        $this->modelManager = $modelManager;
    }
    
    /**
     * Extends attributes with Avalara properties
     * @return \Shopware\Plugins\MoptAvalara\Bootstrap\Database
     * @throws \Exception
     */
    public function install()
    {
        $this->updateDatabase(true);
        return $this;
    }

    /**
     * Hide Avalara attributes
     * @return \Shopware\Plugins\MoptAvalara\Bootstrap\Database
     * @throws \Exception
     */
    public function uninstall()
    {
        $this->updateDatabase(false);
        return $this;
    }

    /**
     * @param bool $displayInBackground
     * @return \Shopware\Plugins\MoptAvalara\Bootstrap\Database
     */
    private function updateDatabase($displayInBackground = true)
    {
        $this->addField(
            self::CATEGORIES_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'string',
            'Avalara Tax Code',
            'This is the Avalara Tax code of the category sent to Avalara.',
            null,
            $displayInBackground
        );

        $this->addField(
            self::CATEGORIES_ATTR_TABLE,
            self::HSCODE_FIELD,
            'string',
            'Avalara Harmonized Classification Code (hsCode)',
            'This is the Avalara Harmonized Classification Code (hsCode) of the article sent to Avalara.',
            null,
            $displayInBackground
        );

        $this->addField(
            self::ARTICLES_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'string',
            'Avalara Tax Code',
            'This is the Avalara Tax code of the article sent to Avalara.',
            null,
            $displayInBackground
        );

        $this->addField(
            self::ARTICLES_ATTR_TABLE,
            self::HSCODE_FIELD,
            'string',
            'Avalara Harmonized Classification Code (hsCode)',
            'This is the Avalara Harmonized Classification Code (hsCode) of the article sent to Avalara.',
            null,
            $displayInBackground
        );

        $this->addField(
            self::USER_ATTR_TABLE,
            self::EXEMPTION_CODE_FIELD,
            'string',
            'Avalara Exemption Code',
            'Here is the exemption code for a use that can be tax-free for you.',
            null,
            $displayInBackground
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::DOC_CODE_FIELD,
            'string',
            null, null, null,
            false
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::TRANSACTION_TYPE_FIELD,
            'string',
            null, null, null,
            false
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::ORDER_CHANGED_FIELD,
            'boolean',
            null, null, null,
            false
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::LANDEDCOST_FIELD,
            'float',
            null, null, null,
            false
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::INSURANCE_FIELD,
            'float',
            null, null, null,
            false
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::INCOTERMS_FIELD,
            'string',
            null, null, null,
            false
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::INSURED_FIELD,
            'boolean',
            null, null, null,
            false
        );

        $this->addField(
            self::ORDER_ATTR_TABLE,
            self::EXPRESS_SHIPPING_FIELD,
            'boolean',
            null, null, null,
            false
        );

        $this->addField(
            self::VOUCHER_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'string',
            'Avalara Tax Code',
            'This is where the Avalara Tax code for vouchers is given, which is sent to Avalara.',
            null,
            $displayInBackground
        );

        $this->addField(
            self::DISPATCH_ATTR_TABLE,
            self::TAXCODE_FIELD,
            'string',
            'Avalara Tax Code',
            'This is the Avalara Tax code for the shipment sent to Avalara. Leave empty to use default.',
            ShippingFactory::TAXCODE,
            $displayInBackground
        );

        $this->addField(
            self::DISPATCH_ATTR_TABLE,
            self::INSURED_FIELD,
            'boolean',
            'Insurance 100%',
            'You can set if a delivery is completely insured.',
            null,
            $displayInBackground
        );

        $this->addField(
            self::DISPATCH_ATTR_TABLE,
            self::EXPRESS_SHIPPING_FIELD,
            'boolean',
            'Express delivery',
            'You can set if this is an express delivery.',
            null,
            $displayInBackground
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
                'displayInBackend' => $displayInBackground,
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
     * @param string $type
     * @param string $label
     * @param string $descr
     * @param string $default
     * @param bool $displayInBackend
     */
    private function addField($table, $name, $type = 'string', $label = '', $descr = '', $default = null, $displayInBackend = true)
    {
        $this->crudService->update(
            $table,
            $name,
            $type,
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
