<?php

namespace Shopware\Plugins\MoptAvalara\Adapter;

use Shopware_Plugins_Backend_MoptAvalara_Bootstrap;
use Shopware\Plugins\MoptAvalara\Logger\Formatter;
use Shopware\Plugins\MoptAvalara\Logger\LogSubscriber;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\AbstractFactory;
use Shopware\Plugins\MoptAvalara\Service\AbstractService;
use Avalara\AvaTaxClient;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * This is the adaptor for avalara's API
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
 */
class AvalaraSDKAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    const SERVICE_NAME = 'AvalaraSdkAdapter';
    
    /**
     * @var string
     */
    const PRODUCTION_ENV = 'production';
    
    /**
     * @var string
     */
    const SANDBOX_ENV = 'sandbox';
    
    /**
     * @var string
     */
    const MACHINE_NAME = 'localhost';
    
    /**
     * @var string
     */
    const SEVICES_NAMESPACE = '\Shopware\Plugins\MoptAvalara\Service\\';
    
    /**
     * @var string
     */
    const FACTORY_NAMESPACE = '\Shopware\Plugins\MoptAvalara\Adapter\Factory\\';
    
    /**
     * @var string
     */
    const LANDED_COST_LOG_FORMAT = '>>>>>>>> %s %s %s <<<<<<<< %s %s %s';

    /**
     *
     * @var \Avalara\AvaTaxClient
     */
    protected $avaTaxClient;
    
    /**
     *
     * @var \Monolog\Logger
     */
    protected $logger;
    
    /**
     *
     * @var string
     */
    protected $pluginName;
    
    /**
     *
     * @var string
     */
    protected $pluginVersion;
    
    /**
     *
     * @var LogSubscriber
     */
    private $logSubscriber;
    
    /**
     *
     * @param string $pluginName
     * @param string $pluginVersion
     */
    public function __construct($pluginName, $pluginVersion)
    {
        $this->pluginName = $pluginName;
        $this->pluginVersion = $pluginVersion;
    }
    
    /**
     * return factory
     *
     * @param string $type
     * @return AbstractFactory
     */
    public function getFactory($type)
    {
        $name = self::FACTORY_NAMESPACE . ucfirst($type);
        
        return new $name($this);
    }
    
    /**
     * get service by type
     *
     * @param string $type
     * @return AbstractService
     */
    public function getService($type)
    {
        $name = self::SEVICES_NAMESPACE . ucfirst($type);
        return new $name($this);
    }
    
    /**
     * @return \Avalara\AvaTaxClient
     */
    public function getAvaTaxClient()
    {
        if ($this->avaTaxClient !== null) {
            return $this->avaTaxClient;
        }

        $avaClient = new AvaTaxClient(
            $this->pluginName,
            $this->pluginVersion,
            $this->getMachineName(),
            $this->getSDKEnv()
        );
        
        $accountNumber = $this->getPluginConfig(Form::ACCOUNT_NUMBER_FIELD);
        $licenseKey = $this->getPluginConfig(Form::LICENSE_KEY_FIELD);
        $avaClient->withSecurity($accountNumber, $licenseKey);
        $this->avaTaxClient = $avaClient;

        // Attach a handler to log all requests
        $avaClient->getHttpClient()->getEmitter()->attach($this->getLogSubscriber());
        
        return $this->avaTaxClient;
    }
    
    /**
     * Lazy load of the LogSubscriber
     * @return LogSubscriber
     */
    public function getLogSubscriber()
    {
        if (null === $this->logSubscriber) {
            $formatter = new Formatter($this->getFormatterTemplate());
            $this->logSubscriber = new LogSubscriber($this->getLogger(), $formatter);
        }
        
        return $this->logSubscriber;
    }
    
    /**
     * Lazy load logger
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        if ($this->logger !== null) {
            return $this->logger;
        }

        //setup monolog
        $this->logger = new Logger('mopt_avalara');
        $logFileName = Form::LOG_FILE_NAME . Form::LOG_FILE_EXT;
        $streamHandler = new RotatingFileHandler(
            $this->getLogDir() . $logFileName,
            $this->getMaxFiles(),
            $this->getLogLevel()
        );
        $this->logger->pushHandler($streamHandler);

        return $this->logger;
    }

    /**
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     * @throws \RuntimeException
     */
    public function getBootstrap()
    {
        if (!$bootstrap = Shopware()->Plugins()->Backend()->get(Shopware_Plugins_Backend_MoptAvalara_Bootstrap::PLUGIN_NAME)) {
            throw new \RuntimeException(Shopware_Plugins_Backend_MoptAvalara_Bootstrap::PLUGIN_NAME . ' is not enabled or installed.');
        }
        
        return $bootstrap;
    }
    
    /**
     * checks first, if module is available / installed
     * @param string $key
     * @return mixed
     */
    public function getPluginConfig($key)
    {
        if (Shopware()->Plugins()->Backend()->get(Shopware_Plugins_Backend_MoptAvalara_Bootstrap::PLUGIN_NAME) && isset(Shopware()->Plugins()->Backend()->MoptAvalara()->Config()->$key)) {
            return Shopware()->Plugins()->Backend()->MoptAvalara()->Config()->$key;
        }
        
        return null;
    }
    
    /**
     * @param int $id
     * @return \Shopware\Models\Order\Order
     */
    public function getOrderById($id)
    {
        $respository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        return $respository->find($id);
    }

    /**
     * @param int $orderNumber
     * @return \Shopware\Models\Order\Order
     */
    public function getOrderByNumber($orderNumber)
    {
        $respository = Shopware()->Models()->getRepository('Shopware\Models\Order\Order');
        return $respository->findOneByNumber($orderNumber);
    }

    /**
     * @return string
     */
    private function getSDKEnv()
    {
        if ($this->getPluginConfig(Form::IS_LIVE_MODE_FIELD)) {
            return self::PRODUCTION_ENV;
        }
        
        return self::SANDBOX_ENV;
    }
    
    /**
     * @return string
     */
    private function getMachineName()
    {
        return self::MACHINE_NAME;
    }

    /**
     * Returns directory for log files
     * @return string
     */
    private function getLogDir()
    {
        return Shopware()->Application()->Kernel()->getLogDir() . '/';
    }

    /**
     * Returns number of days to be stored in log files.
     * @return int
     */
    protected function getMaxFiles()
    {
        if ($rotationDays = $this->getPluginConfig(Form::LOG_ROTATION_DAYS_FIELD)) {
            return $rotationDays;
        }

        return Form::LOGGER_DEFAULT_ROTATING_DAYS;
    }

    /**
     * get monolog log-level by module configuration
     * @return int
     */
    protected function getLogLevel()
    {
        $logLevel = 'ERROR';
        
        if ($overrideLogLevel = $this->getPluginConfig(Form::LOG_LEVEL_FIELD)) {
            $logLevel = $overrideLogLevel;
        }
        
        //set levels
        switch ($logLevel) {
            case 'INFO':
                return Logger::INFO;
            case 'ERROR':
                return Logger::ERROR;
            case 'DEBUG':
            default:
                return Logger::DEBUG;
        }
    }

    /**
     * get formatter template by log level
     * @return string
     */
    protected function getFormatterTemplate()
    {
        $logLevel = $this->getLogLevel();
        switch ($logLevel) {
            case Logger::INFO:
                return Formatter::CLF;
            case Logger::ERROR:
                return Formatter::CLF;
            case 'DEBUG':
            default:
                return Formatter::DEBUG;
        }
    }
    
    /**
     * @param string $docCode
     * @return \stdClass
     */
    public function getTransactionByDocCode($docCode)
    {
        $companyCode = $this->getPluginConfig(Form::COMPANY_CODE_FIELD);
        
        return $this
            ->getAvaTaxClient()
            ->getTransactionByCode($companyCode, $docCode, '')
        ;
    }
}
