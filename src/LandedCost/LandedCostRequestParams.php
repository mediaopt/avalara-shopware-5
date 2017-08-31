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
    /**
     * @var string Param name to be used in Avalara request
     */
    const LANDED_COST_INCOTERMS = 'AvaTax.LandedCost.Incoterms';
    
    /**
     * @var string Param name to be used in Avalara request
     */
    const LANDED_COST_SHIPPING_MODE = 'AvaTax.LandedCost.ShippingMode';
    
    /**
     * @var string Param name to be used in Avalara request
     */
    const LANDED_COST_HTSCODE = 'AvaTax.LandedCost.HTSCode';
    
    /**
     * @var string Param name to be used in Avalara request
     */
    const LANDED_COST_EXPRESS = 'AvaTax.LandedCost.Express';
}
