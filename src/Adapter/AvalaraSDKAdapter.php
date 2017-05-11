<?php

namespace Shopware\Plugins\MoptAvalara\Adapter;

use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;
use Shopware_Plugins_Backend_MoptAvalara_Bootstrap;
use Shopware\Plugins\MoptAvalara\Logger\Formatter;
use Shopware\Plugins\MoptAvalara\Logger\LogSubscriber;
use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Avalara\AvaTaxClient;
use LandedCostCalculationAPILib\LandedCostCalculationAPIClient;
use LandedCostCalculationAPILib\Controllers\BaseController;
use LandedCostCalculationAPILib\Http\HttpCallBack;
use LandedCostCalculationAPILib\Http\HttpRequest;
use LandedCostCalculationAPILib\Http\HttpContext;

/**
 * This is the adaptor for avalara's API
 */
class AvalaraSDKAdapter implements AdapterInterface
{
    const SERVICE_NAME = 'AvalaraSdkAdapter';
    
    const PRODUCTION_ENV = 'production';
    
    const SANDBOX_ENV = 'sandbox';
    
    const MACHINE_NAME = 'localhost';
    
    const SEVICES_NAMESPACE = '\Shopware\Plugins\MoptAvalara\Service\\';
    
    const FACTORY_NAMESPACE = '\Shopware\Plugins\MoptAvalara\Adapter\Factory\\';
    
    const LANDED_COST_LOG_FORMAT = '>>>>>>>> %s %s %s %s <<<<<<<< %s %s %s';

    /**
     *
     * @var \Avalara\AvaTaxClient
     */
    protected $avaTaxClient = null;
    
    /**
     *
     * @var \LandedCostCalculationAPILib\Controllers\LandedCostCalculationAPIController
     */
    protected $avaLandedCostController = null;
    
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
        
        $accountNumber = $this->getPluginConfig(FormCreator::ACCOUNT_NUMBER_FIELD);
        $licenseKey = $this->getPluginConfig(FormCreator::LICENSE_KEY_FIELD);
        $avaClient->withSecurity($accountNumber, $licenseKey);
        $this->avaTaxClient = $avaClient;
        
        // Attach a handler to log all requests
        $formatter = new Formatter($this->getFormatterTemplate());
        $subscriber = new LogSubscriber($this->getLogger(), $formatter);
        $avaClient->getHttpClient()->getEmitter()->attach($subscriber);
        
        return $this->avaTaxClient;
    }
    
    /**
     * @return \LandedCostCalculationAPILib\Controllers\LandedCostCalculationAPIController
     */
    public function getAvaLandedCostController()
    {
        if ($this->avaLandedCostController !== null) {
            return $this->avaLandedCostController;
        }

        $avaLandedCostClient = new LandedCostCalculationAPIClient();
        $avaLandedCostController = $avaLandedCostClient->getLandedCostCalculationAPI();
        $this->setCallbackForLandedCostController($avaLandedCostController);
        
        $this->avaLandedCostController = $avaLandedCostController;

        return $this->avaLandedCostController;
    }
    
    /**
     * 
     * @param BaseController $controller
     * @return \Shopware\Plugins\MoptAvalara\Adapter\AvalaraSDKAdapter
     */
    private function setCallbackForLandedCostController(BaseController $controller)
    {
        $httpCallBack = new HttpCallBack();
        $httpCallBack->setOnBeforeRequest([$this, 'onBeforeRequest']);
        $httpCallBack->setOnAfterRequest([$this, 'onAfterRequest']);
        $controller->setHttpCallBack($httpCallBack);

        return $this;
    }
    
    /**
     * Will setup auth headers
     * @param HttpRequest $httpRequest
     * @return null
     */
    private function onBeforeRequest(HttpRequest $httpRequest) {
        $accountNumber = $this->getPluginConfig(FormCreator::ACCOUNT_NUMBER_FIELD);
        $licenseKey = $this->getPluginConfig(FormCreator::LICENSE_KEY_FIELD);
        
        $headers = $httpRequest->getHeaders();
        $headers['authorization'] = 'avalaraapikey id:' . $accountNumber . ' key:' . $licenseKey;
        $httpRequest->setHeaders($headers);
        
        return null;
    }
    
    /**
     * Will log response
     * @param HttpContext $httpContext
     * @return null
     */
    private function onAfterRequest(HttpContext $httpContext) {
        $this->logResponse($httpContext);
        
        return null;
    }
    
    /**
     * Will log response
     * @param HttpContext $httpContext
     * @return null
     */
    private function logResponse(HttpContext $httpContext) {
        $request = $httpContext->getRequest();
        $response = $httpContext->getResponse();
        
        $message = sprintf(
            self::LANDED_COST_LOG_FORMAT, 
            $request->getMethod(),
            $request->getQueryUrl(),
            json_encode($request->getHeaders()),
            json_encode($request->getParameters()),
            $response->getStatusCode(),
            json_encode($response->getHeaders()),
            $response->getRawBody()
        );
        
        if (200 === $response->getStatusCode()) {
            $this->getLogger()->info($message);
            return null;
        }
        $this->getLogger()->critical($message);
        
        return null;
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
        $logFileName = FormCreator::LOG_FILE_NAME . FormCreator::LOG_FILE_EXT;
        $streamHandler = new \Monolog\Handler\RotatingFileHandler(
            $this->getLogDir() . $logFileName,
            $this->getMaxFiles(),
            $this->getLogLevel()
        );
        $this->logger->pushHandler($streamHandler);

        return $this->logger;
    }
    
    /**
     * 
     * @return \Shopware_Plugins_Backend_MoptAvalara_Bootstrap
     */
    public function getBootstrap()
    {
        if (!$bootstrap = Shopware()->Plugins()->Backend()->get(Shopware_Plugins_Backend_MoptAvalara_Bootstrap::PLUGIN_NAME)) {
            throw new \Exception(Shopware_Plugins_Backend_MoptAvalara_Bootstrap::PLUGIN_NAME . ' is not enabled or installed.');
        }
        
        return $bootstrap;
    }
    
    /**
     * checks first, if module is available / installed
     * @param string $key
     * @return type
     */
    public function getPluginConfig($key)
    {
        if (Shopware()->Plugins()->Backend()->get(Shopware_Plugins_Backend_MoptAvalara_Bootstrap::PLUGIN_NAME) && isset(Shopware()->Plugins()->Backend()->MoptAvalara()->Config()->$key)) {
            return Shopware()->Plugins()->Backend()->MoptAvalara()->Config()->$key;
        }
        
        return null;
    }

    /**
     * @return string
     */
    private function getSDKEnv()
    {
        if ($this->getPluginConfig(FormCreator::IS_LIVE_MODE_FIELD)) {
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
        if ($rotationDays = $this->getPluginConfig(FormCreator::LOG_ROTATION_DAYS_FIELD)) {
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
        
        if ($overrideLogLevel = $this->getPluginConfig(FormCreator::LOG_LEVEL_FIELD)) {
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
}
