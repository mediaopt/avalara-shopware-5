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
use Shopware\Plugins\MoptAvalara\Mail as MailNamespace;

/**
 * this class configures:
 * installment, uninstallment, updates, hooks, events, payment methods
 * 
 * @extends Shopware_Components_Plugin_Bootstrap
 * @author derksen mediaopt GmbH
 */
class Shopware_Plugins_Backend_MoptAvalara_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    /**
     * @var string Plugin identifier
     */
    const PLUGIN_NAME = 'MoptAvalara';
    
    /**
     * @var string
     */
    const SNIPPETS_NAMESPACE = 'frontend/MoptAvalara/messages';

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
     * Perform all necessary install tasks
     *
     * @return array
     * @throws \Exception
     */
    public function install()
    {
        $this
            ->isBcMathInstalled()
            ->registerControllers()
            ->registerEvents()
            ->createForm()
            ->updateDatabase()
        ;

        return ['success' => true, 'invalidateCache' => ['frontend', 'backend', 'proxy']];
    }

    /**
     * Perform all necessary update tasks
     *
     * @return array
     * @throws \Exception
     */
    public function update()
    {
        $this
            ->isBcMathInstalled()
            ->registerControllers()
            ->registerEvents()
            ->createForm()
            ->updateDatabase()
        ;

        return ['success' => true, 'invalidateCache' => ['frontend', 'backend', 'proxy']];
    }

    /**
     * Perform all necessary uninstall tasks
     *
     * @return boolean
     * @throws \Exception
     */
    public function uninstall()
    {
        $this
            ->clearDatabase()
            ->disable()
        ;

        return true;
    }

    /**
     * Extend attributes with avalara properties
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     * @throws \Exception
     */
    private function updateDatabase()
    {
        $bootstrapDatabase = $this->getDatabaseUpdater();
        $bootstrapDatabase->install();

        return $this;
    }


    /**
     * Method will remove avalara properties
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     * @throws \Exception
     */
    private function clearDatabase()
    {
        $bootstrapDatabase = $this->getDatabaseUpdater();
        $bootstrapDatabase->uninstall();

        return $this;
    }

    /**
     * @return Database
     */
    private function getDatabaseUpdater()
    {
        $crudService = $this->get('shopware_attribute.crud_service');
        $em = Shopware()->Models();

        return new Database($crudService, $em);
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
        $subscribers[] = new SubscriberNamespace\OrderSubscriber($this);
        $subscribers[] = new SubscriberNamespace\DocumentSubscriber($this);

        $subscribersWithMailFormatters = $this->addMailFormatterSubscriber($subscribers);

        array_map(
            [$this->Application()->Events(), 'addSubscriber'],
            $subscribersWithMailFormatters
        );
    }
    
    /**
     * 
     * @param \Enlight\Event\SubscriberInterface[] $subscribers
     * @return \Enlight\Event\SubscriberInterface[]
     */
    private function addMailFormatterSubscriber($subscribers = [])
    {
        $templateMailService = $this->get('TemplateMail');
        $config = $this->get('config');
        
        $mailSubscriber = new SubscriberNamespace\SendOrderMailSubscriber($this);
        $mailSubscriber
            ->addMailFormatter(new MailNamespace\BodyHtmlZendMailFormatter($templateMailService, $config))
            ->addMailFormatter(new MailNamespace\BodyTextZendMailFormatter($templateMailService, $config))
        ;
        $subscribers[] = $mailSubscriber;

        return $subscribers;
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

    /**
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    private function isBcMathInstalled()
    {
        if (!extension_loaded('bcmath')) {
            throw new \RuntimeException('This plugin requires the bcmath PHP extension.');
        }

        return $this;
    }
}
