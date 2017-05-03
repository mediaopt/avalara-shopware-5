<?php

namespace Shopware\Plugins\MoptAvalara\Service;


/**
 * Description of CancelTax
 *
 */
class CancelTax extends AbstractService
{
    /**
     * 
     * @param string $docCode
     * @param string $cancelCode
     * @return \stdClass
     */
    public function cancel($docCode, $cancelCode)
    {
        $requestModel = $this->getAdapter()->getFactory('CancelTaxRequest')->build($docCode, $cancelCode);
        
        $response = $this->getSdkMain()->getClient()->post($this->getServiceUrl(), array(
            'auth' => $this->getAuth(),
            'body' => json_encode($requestModel->toArray()),
        ));

        return $response;
    }
}