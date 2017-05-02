<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Shopware\Plugins\MoptAvalara\Model\Address;

/**
 * Description of ValidateAddress
 *
 */
class ValidateAddress extends AbstractService
{

    protected $SERVICE_PATH = '/1.0/address/validate';

    /**
     * 
     * @param Address $address
     * @return \stdClass
     */
    public function validate(Address $address)
    {
        $response = $this->getAdapter()->getClient()->resolveAddress(
            $address->getLine1(),
            $address->getLine2(),
            $address->getLine3(),
            $address->getCity(),
            $address->getRegion(),
            $address->getPostalCode(),
            $address->getCountry()
        );
        
        return $response;
    }

    /**
     * 
     * @param Address $checkedAddress
     * @param \stdClass $response
     * @return array
     */
    public function getAddressChanges(Address $checkedAddress, $response)
    {
        $changes = array();

        if (empty($response) || !is_object($response) || empty($response->validatedAddresses)) {
            return $changes;
        }

        $suggestedAddress = $response->validatedAddresses[0];
        foreach ($checkedAddress as $key => $value) {
            $lcFirsKey = lcfirst($key);
            if (isset($suggestedAddress->$lcFirsKey) && $suggestedAddress->$lcFirsKey != $value) {
                $changes[$key] = $suggestedAddress->$lcFirsKey;
            }
        }

        return $changes;
    }

}
