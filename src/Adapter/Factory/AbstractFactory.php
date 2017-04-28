<?php
namespace Mediaopt\Avalara\Adapter\Factory;

use Mediaopt\Avalara\Sdk\Main;
/**
 * Description of AbstractFactory
 *
 */
abstract class AbstractFactory
{
    /**
     *
     * @var Main
     */
    protected $sdkMain;
    
    /**
     *
     * @var \Mediaopt\Avalara\Adapter\Main
     */
    protected $adapterMain;
    
    /**
     *
     * @var array
     */
    protected $userData = null;
    
    /**
     *
     * @var array
     */
    protected $pluginConfig = null;
    
    public function __construct(Main $sdkMain, \Mediaopt\Avalara\Adapter\Main $adapterMain)
    {
        $this->sdkMain = $sdkMain;
        $this->adapterMain = $adapterMain;
    }
    
    public function getSdkMain()
    {
        return $this->sdkMain;
    }

    public function setSdkMain(Main $sdkMain)
    {
        $this->sdkMain = $sdkMain;
    }
    
    public function getAdapterMain()
    {
        return $this->adapterMain;
    }

    public function setAdapterMain(\Mediaopt\Avalara\Adapter\Main $adapterMain)
    {
        $this->adapterMain = $adapterMain;
    }
    
    protected function getUserData()
    {
        if ($this->userData !== null) {
            return $this->userData;
        }
        return $this->userData = Shopware()->Modules()->Admin()->sGetUserData();
    }
    
    protected function getPluginConfig()
    {
        if ($this->pluginConfig !== null) {
            return $this->pluginConfig;
        }
        return $this->pluginConfig = Shopware()->Plugins()->Backend()->MoptAvalara()->Config();
    }
}
