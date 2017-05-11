<?php

namespace Shopware\Plugins\MoptAvalara\Service;

/**
 * Description of GetLandedCost
 *
 */
class GetLandedCost extends AbstractService
{
    /**
     * 
     * @param \LandedCostCalculationAPILib\Models\CalculateRequest $request
     * @return \stdClass
     */
    public function calculate($request)
    {
        $controller = $this
            ->getAdapter()
            ->getAvaLandedCostController();
        
        return $controller->createCalculate($request);
    }
}
