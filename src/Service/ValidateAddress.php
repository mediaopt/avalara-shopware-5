<?php

namespace Shopware\Plugins\MoptAvalara\Service;

use Shopware\Plugins\MoptAvalara\Model\Address;

/**
 * Description of ValidateAddress
 *
 */
class ValidateAddress extends AbstractService
{
    /**
     * 
     * @param \Shopware\Plugins\MoptAvalara\Model\Address $address
     * @return \stdClass
     */
    public function validate(Address $address)
    {
        $response = $this->getAdapter()->getClient()->resolveAddress(
            $address->line1,
            $address->line2,
            $address->line3,
            $address->city,
            $address->region,
            $address->postalCode,
            $address->country
        );
        
        return $response;
    }

    /**
     * 
     * @param \Shopware\Plugins\MoptAvalara\Model\Address $checkedAddress
     * @param \stdClass $response
     * @return array
     */
    public function getAddressChanges(Address $checkedAddress, $response)
    {
        $changes = array();
        if (empty($response) || !is_object($response) || empty($response->validatedAddresses)) {
            return $changes;
        }

        /* @var $suggestedAddress \Shopware\Plugins\MoptAvalara\Model\Address */
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
