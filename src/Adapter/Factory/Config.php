<?php

namespace Mediaopt\Avalara\Adapter\Factory;

/**
 * Description of Config
 *
 */
class Config extends AbstractFactory
{
    public function build()
    {
        $pluginConfig = $this->getPluginConfig();
        $config = new \Mediaopt\Avalara\Sdk\Model\Config();
        $config->setIsLiveMode($pluginConfig->mopt_avalara__is_live_mode);
        $config->setApiUsername($pluginConfig->mopt_avalara__account_number);
        $config->setApiPasswort($pluginConfig->mopt_avalara__license_key);
        return $config;
    }
}
