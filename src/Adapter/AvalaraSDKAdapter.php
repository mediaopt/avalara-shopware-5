<?php

namespace Shopware\Plugins\MoptAvalara\Adapter;

use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;
use Shopware_Plugins_Backend_MoptAvalara_Bootstrap;
use Shopware\Plugins\MoptAvalara\Logger\Formatter;
use Shopware\Plugins\MoptAvalara\Logger\LogSubscriber;
use Avalara\AvaTaxClient;

/**
 * Description of Main
 *
 */
class AvalaraSDKAdapter implements AdapterInterface
{
    const NAME = 'MoptAvalaraShopware4';
    
    const PRODUCTION_ENV = 'production';
    
    const SENDBOX_ENV = 'sandbox';
    
    const MACHINE_NAME = 'localhost';

    /**
     *
     * @var \Avalara\AvaTaxClient
     */
    protected $client = null;
    
    /**
     *
     * @var \Monolog\Logger
     */
    protected $logger = null;
    
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
        $name = __NAMESPACE__ . '\Factory\\' . $type;
        return new $name(Shopware()->Container()->get('MediaoptAvalaraSdkMain'), $this);
    }
    
    /**
     * @return \Avalara\AvaTaxClient
     */
    public function getClient()
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $avaClient = new AvaTaxClient(
            $this->pluginName, 
            $this->pluginVersion, 
            $this->getMachineName(), 
            $this->getSDKEnv()
        );
        
        $accountNumber = $this->getPluginConfig('mopt_avalara__account_number');
        $licenseKey = $this->getPluginConfig('mopt_avalara__license_key');
        $avaClient->withSecurity($accountNumber, $licenseKey);
        $this->client = $avaClient;
        
        // Attach a handler to log all requests
        $formatter = new Formatter($this->getFormatterTemplate());
        $subscriber = new LogSubscriber($this->getLogger(), $formatter);
        $avaClient->getHttpClient()->getEmitter()->attach($subscriber);
        
        return $this->client;
    }
    
    /**
     * @return string
     */
    private function getSDKEnv()
    {
        if ($env = $this->getPluginConfig('mopt_avalara__is_live_mode')) {
            return self::PRODUCTION_ENV;
        }
        
        return self::SENDBOX_ENV;
    }
    
    /**
     * @return string
     */
    private function getMachineName()
    {
        return self::MACHINE_NAME;
    }
    
    /**
     * lazy load logger
     * @return \Monolog\Logger
     */
    public function getLogger()
    {
        if ($this->logger !== null) {
            return $this->logger;
        }

        //setup monolog
        $this->logger = new \Monolog\Logger('mo_avalara');
        $logFileName = Shopware_Plugins_Backend_MoptAvalara_Bootstrap::LOG_FILE_NAME . Shopware_Plugins_Backend_MoptAvalara_Bootstrap::LOG_FILE_EXT;
        $streamHandler = new \Monolog\Handler\RotatingFileHandler(
            $this->getLogDir() . $logFileName,
            $this->getMaxFiles(),
            $this->getLogLevel()
        );
        $this->logger->pushHandler($streamHandler);

        return $this->logger;
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
        if ($rotationDays = $this->getPluginConfig('mopt_avalara_log_rotation_days')) {
            return $rotationDays;
        }

        return Shopware_Plugins_Backend_MoptAvalara_Bootstrap::DEFAULT_ROTATING_DAYS;
    }

    /**
     * get monolog log-level by module configuration
     * @return int
     */
    protected function getLogLevel()
    {
        $logLevel = 'ERROR';
        
        if ($overrideLogLevel = $this->getPluginConfig('mopt_avalara_loglevel')) {
            $logLevel = $overrideLogLevel;
        }
        
        //set levels
        switch ($logLevel) {
            case 'INFO':
                return \Monolog\Logger::INFO;
            case 'ERROR':
                return \Monolog\Logger::ERROR;
            case 'DEBUG':
            default:
                return \Monolog\Logger::DEBUG;
        }
    }

    /**
     * 
     * @param string $messageFormat
     * @return type
     */
    private function createGuzzleLoggingMiddleware($messageFormat)
    {
        return \GuzzleHttp\Ring\Client\Middleware::log(
            $this->getLogger(),
            new \GuzzleHttp\MessageFormatter($messageFormat)
        );
    }

    /**
     * get formatter template by log level
     * @return string
     */
    protected function getFormatterTemplate()
    {
        $logLevel = $this->getLogLevel();
        switch ($logLevel) {
            case \Monolog\Logger::INFO:
                return Formatter::CLF;
            case \Monolog\Logger::ERROR:
                return Formatter::CLF;
            case 'DEBUG':
            default:
                return Formatter::DEBUG;
        }
    }
    
    /**
     * checks first, if module is available / installed
     * @param string $key
     * @return type
     */
    protected function getPluginConfig($key)
    {
        if (Shopware()->Plugins()->Backend()->get(Shopware_Plugins_Backend_MoptAvalara_Bootstrap::PLUGIN_NAME) && isset(Shopware()->Plugins()->Backend()->MoptAvalara()->Config()->$key)) {
            return Shopware()->Plugins()->Backend()->MoptAvalara()->Config()->$key;
        }
        
        return null;
    }
}
