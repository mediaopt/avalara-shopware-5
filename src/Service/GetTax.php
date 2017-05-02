<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Shopware\Plugins\MoptAvalara\Model\DocumentType;
use Shopware\Plugins\MoptAvalara\Model\GetTaxRequest;

/**
 * Description of GetTax
 *
 */
class GetTax extends AbstractService
{

    protected $SERVICE_PATH = '/1.0/tax/get';
    
    public function call(GetTaxRequest $getTaxRequest)
    {
        $response = $this->getSdkMain()->getClient()->post($this->getServiceUrl(), array(
            'auth' => $this->getAuth(),
            'body' => json_encode($getTaxRequest->toArray()),
        ));
        
        return $response->json();
    }
}
