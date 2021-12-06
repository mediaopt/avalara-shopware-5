<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Service;

use Shopware\Bundle\PluginInstallerBundle\Service\InstallerService;
use Shopware\Plugins\MoptAvalara\Adapter\AdapterInterface;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
class CheckoutPrice extends AbstractService
{
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
    }

    public function isDvsGrossFixedPluginActive()
    {
        try {
            $pluginManager = Shopware()->Container()->get('shopware_plugininstaller.plugin_manager');
            $plugin = $pluginManager->getPluginByName('DvsnArticleFixedGross');

            return $plugin->getActive();
        } catch (\Exception $e) {
            return false;
        }
    }
}
