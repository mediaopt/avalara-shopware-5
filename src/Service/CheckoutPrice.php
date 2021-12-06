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

    public function getPriceWithTaxIncluded(float $price, float $taxRate): float
    {
        return $price - (round($price, 2) * round(($taxRate / 100), 2));
    }

    public function getGrossPriceWithTax(array $price): float
    {
        return ($price['price'] * (1 + (19 / 100)) / (1 + (0 / 100))) + $price['tax'];
    }

    public function isDvsGrossFixedPluginActive(): bool
    {
        /** @var InstallerService $pluginManager */
        $pluginManager = Shopware()->Container()->get('shopware_plugininstaller.plugin_manager');
        $plugin = $pluginManager->getPluginByName('DvsnArticleFixedGross');

        return $plugin->getActive();
    }
}