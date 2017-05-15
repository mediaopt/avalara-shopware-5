<?php

use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\ShippingFactory;
use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;

/**
 * this class configures:
 * installment, uninstallment, updates, hooks, events, payment methods
 *
 * @extends Shopware_Components_Plugin_Bootstrap
 */
class Shopware_Plugins_Backend_MoptAvalara_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    const PLUGIN_NAME = 'MoptAvalara';

    /**
     * get plugin capabilities
     *
     * @return array
     */
    public function getCapabilities()
    {
        return [
            'install' => true,
            'update' => true,
            'enable' => true,
        ];
    }

    /**
     * returns the information of plugin as array.
     *
     * @return array
     */
    public function getInfo()
    {
        $info = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
        $img = base64_encode(file_get_contents(__DIR__ . '/logo.png'));
        $info['description'] = sprintf($info['description'], $img);

        return $info;
    }

    /**
     * returns the version of plugin as string.
     *
     * @return string
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
        return $info['version'];
    }

    /**
     * perform all neccessary install tasks and return true if successful
     *
     * @return array
     * @throws \Exception
     */
    public function install()
    {  
        $this
            ->registerControllers()
            ->registerEvents()
            ->addAttributes()
            ->createForm()
        ;

        return ['success' => true, 'invalidateCache' => ['backend', 'proxy']];
    }

    /**
     * perform update depending on plugin version
     *
     * @param string $oldVersion
     * @return array|bool
     */
    public function update($oldVersion)
    {
        if (version_compare($oldVersion, '1.0.2', '<=')) {
            $this->update_1_1_0();
        }
        if (version_compare($oldVersion, '2.0.0', '<=')) {
            $this->update_2_0_0();
        }
        return true;
    }

    /**
     *  compatibility to Shopware 5.2.
     */
    protected function update_1_1_0()
    {
        $this->addAttributes_1_1_0();
    }

    /**
     * @TODO Add new fields to orders, shipping methods, categories and articles
     *  New fields
     */
    protected function update_2_0_0()
    {
        $this->addAttributes_2_0_0();
    }

    /**
     * perform all neccessary uninstall tasks and return true if successful
     *
     * @return boolean
     */
    public function uninstall()
    {
        $this->disable();
        return true;
    }

    /**
     * Create SDK
     */
    public function afterInit()
    {
        $this->Application()->Loader()->registerNamespace('Shopware\\Plugins\\MoptAvalara', $this->Path());
        require_once $this->Path() . 'vendor/autoload.php';
        // @TODO remove this require after avalara will fix autoloading
        require_once $this->Path() . 'vendor/avalara/avataxclient/src/AvaTaxClient.php';
        $serviceName = AvalaraSDKAdapter::SERVICE_NAME;
        Shopware()->Container()->set($serviceName, $this->createAvalaraSdkAdapter());
        
        //add snippets
        $this->get('Snippets')->addConfigDir($this->Path() . 'Snippets/');
    }
    
    /**
     * 
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    private function createAvalaraSdkAdapter()
    {
        return new AvalaraSDKAdapter($this->getName(), $this->getVersion());
    }
    
    /**
     * register controllers
     * @throws \Exception
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap;
     */
    protected function registerControllers()
    {
        $this->registerController('Backend', 'MoptAvalaraBackendProxy');
        $this->registerController('Backend', 'MoptAvalara');
        $this->registerController('Backend', 'MoptAvalaraLog');
        
        return $this;
    }
    
    /**
     * register for several events to extend shop functions
     * @throws \Exception
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    protected function registerEvents()
    {
        $this->subscribeEvent('Enlight_Controller_Front_DispatchLoopStartup', 'onDispatchLoopStartup');
        
        return $this;
    }

    /**
     * register all subscriber class for dynamic event subscription without plugin reinstallation
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onDispatchLoopStartup(Enlight_Event_EventArgs $args)
    {
        $subscribers = [];
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\AddressCheck($this);
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\Templating($this);
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\GetTax($this);
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\AdjustTax($this);

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }

    /**
     * extend attributes with avalara properties
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     * @throws \Exception
     */
    private function addAttributes()
    {
        $this
            ->addAttributes_1_1_0()
            ->addAttributes_2_0_0()
        ;
        
        return $this;
    }

    /**
     * extend attributes with avalara properties
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     * @throws \Exception
     */
    private function addAttributes_1_1_0()
    {
        $attributeCrudService = $this->getCrudService();
        
        $attributeCrudService->update('s_categories_attributes', 'mopt_avalara_taxcode', 'string', [
            'label' => 'Avalara Tax Code',
            'supportText' => 'Hier wird der Avalara Tax-Code der Kategorie angegeben, der an Avalara übersendet wird.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);
        $attributeCrudService->update('s_articles_attributes', 'mopt_avalara_taxcode', 'string', [
            'label' => 'Avalara Tax Code',
            'supportText' => 'Hier wird der Avalara Tax-Code des Artikel angegeben, der an Avalara übersendet wird.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);
        $attributeCrudService->update('s_user_attributes', 'mopt_avalara_exemption_code', 'string', [
            'label' => 'Avalara Exemption Code',
            'supportText' => 'Hier wird der Exemption-Code für einen Benutzen angegeben, der steuerfrei bei Ihnen einkaufen kann.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);
        $attributeCrudService->update('s_order_attributes', 'mopt_avalara_doc_code', 'string', [
            'displayInBackend' => false,
            'custom' => true,
        ]);
        $attributeCrudService->update('s_emarketing_vouchers_attributes', 'mopt_avalara_taxcode', 'string', [
            'label' => 'Avalara Tax Code',
            'supportText' => 'Hier wird der Avalara Tax-Code für Gutscheine angegeben, der an Avalara übersendet wird.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);
        $attributeCrudService->update('s_order_attributes', 'mopt_avalara_order_changed', 'boolean', [
            'displayInBackend' => false,
            'custom' => true,
        ]);
        
        $this->refreshAttributeModels([
            's_categories_attributes',
            's_articles_attributes',
            's_user_attributes',
            's_order_attributes',
            's_emarketing_vouchers_attributes',
        ]);
        return $this;
    }
    
    /**
     * add new attributes with avalara landed cost properties
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     * @throws \Exception
     */
    private function addAttributes_2_0_0()
    {
        $attributeCrudService = $this->getCrudService();
        
        $attributeCrudService->update('s_premium_dispatch_attributes', 'mopt_avalara_taxcode', 'string', [
            'label' => 'Avalara Tax Code',
            'supportText' => 'Hier wird der Avalara Tax-Code für den Versand angegeben, der an Avalara übersendet wird.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
            ], 
            null, 
            false, 
            ShippingFactory::TAXCODE
        );
        
        $attributeCrudService->update('s_categories_attributes', 'mopt_avalara_hscode', 'string', [
            'label' => 'Avalara Harmonized Classification Code (hsCode)',
            'supportText' => 'Hier wird der Avalara Harmonized Classification Code (hsCode) der Kategorie angegeben, der an Avalara übersendet wird.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);

        $attributeCrudService->update('s_articles_attributes', 'mopt_avalara_hscode', 'string', [
            'label' => 'Avalara Harmonized Classification Code (hsCode)',
            'supportText' => 'Hier wird der Avalara Harmonized Classification Code (hsCode) des Artikel angegeben, der an Avalara übersendet wird.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);
        
        $attributeCrudService->update('s_premium_dispatch_attributes', 'mopt_avalara_express_shipping', 'boolean', [
            'label' => 'Express delivery',
            'supportText' => 'You can designate this delivery set an express delivery.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);
        
        $attributeCrudService->update('s_premium_dispatch_attributes', 'mopt_avalara_insured', 'boolean', [
            'label' => 'Insurance 100%',
            'supportText' => 'You can set if a delivery is completely insured.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
            'custom' => true,
        ]);
        
        $attributeCrudService->update('s_core_countries_attributes', 'mopt_avalara_incoterms', 'combobox', [
                'label' => 'Incoterms for Landed cost',
                'supportText' => 'Terms of sale. Used to determine buyer obligations for a landed cost.',
                'helpText' => '',
                'translatable' => false,
                'displayInBackend' => true,
                'position' => 10,
                'custom' => true,
                'defaultValue' => FormCreator::INCOTERMS_DEFAULT,
                'arrayStore' => [
                    [
                        'key' => FormCreator::INCOTERMS_DDP, 
                        'value' => FormCreator::INCOTERMS_DDP_LABEL
                    ],
                    [
                        'key' => FormCreator::INCOTERMS_DAP, 
                        'value' => FormCreator::INCOTERMS_DAP_LABEL
                    ],
                    [
                        'key' => FormCreator::INCOTERMS_DEFAULT, 
                        'value' => FormCreator::INCOTERMS_DEFAULT
                    ],
                ],
            ], 
            null, 
            false, 
            FormCreator::INCOTERMS_DEFAULT
        );
        
        $attributeCrudService->update('s_order_attributes', 'mopt_avalara_incoterms', 'string', [
            'displayInBackend' => false,
            'custom' => true,
        ]);
        
        $attributeCrudService->update('s_order_attributes', 'mopt_avalara_insured', 'boolean', [
            'displayInBackend' => false,
            'custom' => true,
        ]);
        
        $attributeCrudService->update('s_order_attributes', 'mopt_avalara_express_shipping', 'boolean', [
            'displayInBackend' => false,
            'custom' => true,
        ]);
        
        $attributeCrudService->update('s_order_attributes', 'mopt_avalara_express_landedcost', 'string', [
            'displayInBackend' => false,
            'custom' => true,
        ]);
        
        $this->refreshAttributeModels([
            's_premium_dispatch_attributes',
            's_categories_attributes',
            's_articles_attributes',
            's_core_countries_attributes',
            's_order_attributes,'
        ]);
        
        return $this;
    }
    
    /**
     * @return \Shopware\Bundle\AttributeBundle\Service\CrudService
     */
    private function getCrudService()
    {
        return $this->get('shopware_attribute.crud_service');
    }

    /**
     * create config form
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    private function createForm()
    {
        $formCreator = new FormCreator($this);
        $formCreator->createForms();
        
        return $this;
    }
    
    /**
     * 
     * @param array $tables
     */
    private function refreshAttributeModels($tables = [])
    {
        $metaDataCacheDoctrine = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCacheDoctrine->deleteAll();

        Shopware()->Models()->generateAttributeModels($tables);
    }
}
