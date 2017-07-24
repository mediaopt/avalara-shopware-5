<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Shopware\Plugins\MoptAvalara\Bootstrap\Database;
use Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter;
use Shopware\Plugins\MoptAvalara\Subscriber as SubscriberNamespace;

/**
 * this class configures:
 * installment, uninstallment, updates, hooks, events, payment methods
 * 
 * @extends Shopware_Components_Plugin_Bootstrap
 * @author derksen mediaopt GmbH
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
            ->createForm()
            ->updateDatabase()
        ;

        return ['success' => true, 'invalidateCache' => ['frontend', 'backend', 'proxy']];
    }

    /**
     * Extend attributes with avalara properties
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     * @throws \Exception
     */
    private function updateDatabase()
    {
        $crudService = $this->get('shopware_attribute.crud_service');
        $modelManager = Shopware()->Models();
        $bootstrapDatabase = new Database($crudService, $modelManager);
        $bootstrapDatabase->install();
        
        return $this;
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
        $subscribers[] = new SubscriberNamespace\AddressSubscriber($this);
        $subscribers[] = new SubscriberNamespace\TemplatingSubscriber($this);
        $subscribers[] = new SubscriberNamespace\CheckoutSubscriber($this);
        $subscribers[] = new SubscriberNamespace\BasketSubscriber($this);
        $subscribers[] = new SubscriberNamespace\BackendOrderUpdateSubscriber($this);

        foreach ($subscribers as $subscriber) {
            $this->Application()->Events()->addSubscriber($subscriber);
        }
    }

    /**
     * create config form
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    private function createForm()
    {
        $pluginConfigForm = new Form($this);
        $pluginConfigForm->create();
        
        return $this;
    }
}
