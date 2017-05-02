<?php
error_reporting( E_ALL );
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
        return array(
            'install' => true,
            'update' => true,
            'enable' => true,
        );
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
        $this->registerControllers();
        $this->registerEvents();
        $this->addAttributes();
        $this->createForm();

        return array('success' => true, 'invalidateCache' => array('backend', 'proxy'));
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
        $this->addAttributes();
    }

    /**
     * @TODO Add new fields to categories and articles
     *  New fields
     */
    protected function update_2_0_0()
    {
        
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
        Shopware()->Container()->set('AvalaraSdkAdapter', $this->getAvalaraSdkAdapter());
        
        //add snippets
        $this->get('Snippets')->addConfigDir($this->Path() . 'Snippets/');
    }
    
    /**
     * 
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface
     */
    private function getAvalaraSdkAdapter()
    {
        return new \Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter($this->getName(), $this->getVersion());
    }
    
    /**
     * register controllers
     * @throws \Exception
     */
    protected function registerControllers()
    {
        $this->registerController('Backend', 'MoptAvalaraBackendProxy');
        $this->registerController('Backend', 'MoptAvalara');
        $this->registerController('Backend', 'MoptAvalaraLog');
    }
    
    /**
     * register for several events to extend shop functions
     * @throws \Exception
     */
    protected function registerEvents()
    {
        $this->subscribeEvent('Enlight_Controller_Front_DispatchLoopStartup', 'onDispatchLoopStartup');
    }

    /**
     * register all subscriber class for dynamic event subscription without plugin reinstallation
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onDispatchLoopStartup(Enlight_Event_EventArgs $args)
    {
        $container = Shopware()->Container();
        $subscribers = array();
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\AddressCheck($this, $container);
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\Templating($this);
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\GetTax($this);
        $subscribers[] = new Shopware\Plugins\MoptAvalara\Subscriber\AdjustTax($this);

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }

    /**
     * extend attributes with avalara properties
     * @throws \Exception
     */
    public function addAttributes()
    {
        /** @var \Shopware\Bundle\AttributeBundle\Service\CrudService $attributeCrudService */
        $attributeCrudService = $this->get('shopware_attribute.crud_service');
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
        $attributeCrudService->update('s_premium_dispatch_attributes', 'mopt_avalara_taxcode', 'string', [
            'label' => 'Avalara Tax Code',
            'supportText' => 'Hier wird der Avalara Tax-Code für den Versand angegeben, der an Avalara übersendet wird.',
            'helpText' => '',
            'translatable' => false,
            'displayInBackend' => true,
            'position' => 10,
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
    }

    /**
     * create config form
     */
    public function createForm()
    {
        $formCreator = new \Shopware\Plugins\MoptAvalara\Util\FormCreator($this);
        $formCreator->createForms();
    }
}
