<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Service;

use Avalara\AddressLocationInfo;
use Avalara\AddressResolutionModel;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
class ValidateAddress extends AbstractService
{
    /**
     * Ignore any difference in this address parts
     * @var array
     */
    private static $ignoreAddressParts = [
        'region',
        'latitude',
        'longitude',
    ];
    
    /**
     *
     * @param \Avalara\AddressLocationInfo $address
     * @return AddressResolutionModel
     */
    public function validate(AddressLocationInfo $address)
    {
        return $this->getAdapter()->getAvaTaxClient()->resolveAddressPost($address);
    }

    /**
     *
     * @param \Avalara\AddressLocationInfo $checkedAddress
     * @param \stdClass $response
     * @return array
     */
    public function getAddressChanges(AddressLocationInfo $checkedAddress, $response)
    {
        $changes = [];
        if (null === $response || !is_object($response) || empty($response->validatedAddresses)) {
            return $changes;
        }

        /* @var $suggestedAddress \Avalara\AddressLocationInfo */
        $suggestedAddress = $response->validatedAddresses[0];

        foreach ($checkedAddress as $key => $value) {
            //Skip the region key
            if (in_array($key, self::$ignoreAddressParts, false)) {
                continue;
            }
            if (isset($suggestedAddress->$key) && $suggestedAddress->$key !== (string)$value) {
                $changes[$key] = $suggestedAddress->$key;
            }
        }

        if (isset($response->messages) && count($response->messages) >= 1) {
            $changes['IsInvalidAddress'] = true;
        }

        return $changes;
    }
}
