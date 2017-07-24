<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\LandedCost;

/**
 * Config names holder for the Landed cost features
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\LandedCost
 */
interface LandedCostRequestParams
{
    const LANDED_COST_INCOTERMS = 'AvaTax.LandedCost.Incoterms';
    const LANDED_COST_SHIPPING_MODE = 'AvaTax.LandedCost.ShippingMode';
    const LANDED_COST_HTSCODE = 'AvaTax.LandedCost.HTSCode';
    const LANDED_COST_EXPRESS = 'AvaTax.LandedCost.Express';
}
