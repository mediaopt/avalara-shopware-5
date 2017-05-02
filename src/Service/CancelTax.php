<?php

namespace Shopware\Plugins\MoptAvalara\Service;


/**
 * Description of CancelTax
 *
 */
class CancelTax extends AbstractService
{

    protected $SERVICE_PATH = '/1.0/tax/cancel';

    public function call($docCode, $cancelCode)
    {
        $requestModel = $this->getAdapter()->getFactory('CancelTaxRequest')->build($docCode, $cancelCode);
        
        $response = $this->getSdkMain()->getClient()->post($this->getServiceUrl(), array(
            'auth' => $this->getAuth(),
            'body' => json_encode($requestModel->toArray()),
        ));

        return $response->json();
    }
}