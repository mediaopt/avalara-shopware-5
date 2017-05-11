<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use LandedCostCalculationAPILib\Models\CalculateRequest;

/**
 * Description of GetLandedCost
 *
 */
class GetLandedCost extends AbstractService
{
    /**
     * 
     * @param CalculateRequest $request
     * @return \stdClass
     */
    public function calculate(CalculateRequest $request)
    {
        $controller = $this
            ->getAdapter()
            ->getAvaLandedCostController();
        
        return $controller->createCalculate($request);
    }
}
