<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Avalara\AddressLocationInfo;
use GuzzleHttp\Exception\TransferException;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\AddressFactory;
use Shopware\Plugins\MoptAvalara\Bootstrap\Form;
use Shopware_Plugins_Backend_MoptAvalara_Bootstrap as AvalaraBootstrap;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class AddressSubscriber extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_Frontend_Checkout_Confirm' => 'onBeforeCheckoutConfirm',
            'Enlight_Controller_Action_PostDispatch_Frontend_Address' => 'onPostDispatchFrontendAddress',
        ];
    }

    /**
     * perform address check
     * @param \Enlight_Event_EventArgs $args
     * @return bool|void
     */
    public function onBeforeCheckoutConfirm(\Enlight_Event_EventArgs $args)
    {
        $session = $this->getSession();
        $adapter = $this->getAdapter();
        $address = $adapter->getFactory('AddressFactory')->buildDeliveryAddress();

        if (!$this->isAddressToBeValidated($address) || empty($args->getSubject()->View()->sUserLoggedIn)) {
            $adapter->getLogger()->info('address is not to be validated.');
            return;
        }
        try {
            $userData = $args->getSubject()->View()->sUserData;
            $adapter->getLogger()->info('validating address.');
            /* @var $service \Shopware\Plugins\MoptAvalara\Service\ValidateAddress */
            $service = $adapter->getService('validateAddress');
            $response = $service->validate($address);
            $session->MoptAvalaraCheckedAddress = $this->getAddressHash($address);
            $activeShippingAddressId = $session->offsetGet('checkoutShippingAddressId', null)
                ?: $userData['additional']['user']['default_shipping_address_id'];

            if ($changes = $service->getAddressChanges($address, $response)) {
                $args->getSubject()->forward('edit', 'address', null, [
                    'MoptAvalaraAddressChanges' => $changes,
                    'sTarget' => 'checkout',
                    'id' => $activeShippingAddressId
                ]);
                $session->MoptAvalaraValidAddress = false;

                return true;
            }

            $session->MoptAvalaraValidAddress = true;
        } catch (TransferException $e) {
            //address check failed - nothing to do
            $adapter->getLogger()->info('address check failed: ' . $e->getMessage());
        }
    }

    /**
     * check if address has aready been validated
     * @param \Avalara\AddressLocationInfo $address
     * @return boolean
     */
    protected function isAddressToBeValidated(AddressLocationInfo $address)
    {
        $session = $this->getSession();
        if (!$this->isCountryForDelivery($address->country)) {
            return false;
        }
        
        if (!$session->MoptAvalaraCheckedAddress) {
            return true;
        }
        
        if ($session->MoptAvalaraCheckedAddress !== $this->getAddressHash($address)) {
            return true;
        }

        $validAddressRequired = $this
            ->getAdapter()
            ->getPluginConfig(Form::ADDRESS_VALIDATION_REQUIRED_FIELD)
        ;

        if (!$session->MoptAvalaraValidAddress && $validAddressRequired) {
            return true;
        }

        return false;
    }
    
    /**
     *
     * @param string $country
     * @return bool
     */
    private function isCountryForDelivery($country)
    {
        $countriesForDelivery = $this
            ->getAdapter()
            ->getPluginConfig(Form::ADDRESS_VALIDATION_COUNTRIES_FIELD)
        ;

        switch ($countriesForDelivery) {
            case Form::DELIVERY_COUNTRY_NO_VALIDATION:
                return false;
            case Form::DELIVERY_COUNTRY_USA:
                if ($country === AddressFactory::COUNTRY_CODE__US) {
                    return true;
                }
                break;
            case Form::DELIVERY_COUNTRY_CANADA:
                if ($country === AddressFactory::COUNTRY_CODE__CA) {
                    return true;
                }
                break;
            case Form::DELIVERY_COUNTRY_USA_AND_CANADA:
                $usaAndCanada = [
                    AddressFactory::COUNTRY_CODE__CA,
                    AddressFactory::COUNTRY_CODE__US
                ];
                
                if (in_array($country, $usaAndCanada, true)) {
                    return true;
                }
                break;
        }
        
        return false;
    }

    /**
     * get hash of given address
     *
     * @param \Avalara\AddressLocationInfo $address
     * @return string
     */
    protected function getAddressHash(AddressLocationInfo $address)
    {
        return md5(serialize($address));
    }

    /**
     * Show normalized shipping address
     *
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchFrontendAddress(\Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();

        if ($request->getActionName() !== 'edit') {
            return;
        }

        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->getBootstrap()->Path() . 'Views/');
        $view->extendsTemplate('frontend/register/mopt_avalara__shipping_fieldset.tpl');
        $view->extendsTemplate('frontend/account/mopt_avalara__shipping.tpl');

        if (!$changes = $request->getParam('MoptAvalaraAddressChanges')) {
            return;
        }

        if (isset($changes['IsInvalidAddress'])) {
            $this->addErrorMessage($view, 'shippingAddressInvalid');
            unset($changes['IsInvalidAddress']);

            return;
        }

        $formData = $args->getSubject()->View()->formData;
        $view->assign('sUserDataOld', $formData);
        foreach ($changes as $key => $value) {
            switch ($key) {
                case 'city':
                    $formData['city'] = $value;
                    break;
                case 'line1':
                    $formData['street'] = $value;
                    break;
                case 'postalCode':
                    $formData['zipcode'] = $value;
                    break;
            }
        }
        $view->assign('formData', $formData);
        $view->assign('MoptAvalaraAddressChanges', $changes);

        $this->addErrorMessage($view, 'shippingAddressChangesFound');
    }

    /**
     * Add error message
     *
     * @param \Enlight_View_Default $view
     * @param string                $message
     */
    protected function addErrorMessage(\Enlight_View_Default $view, $message)
    {
        $snippets = $this
            ->getContainer()
            ->get('snippets')
            ->getNamespace(AvalaraBootstrap::SNIPPETS_NAMESPACE)
        ;


        $errorMessages = $view->getAssign('sErrorMessages')
            ? $view->getAssign('error_messages')
            : $view->getAssign('sErrorMessages')
        ;

        if (!is_array($errorMessages)) {
            $errorMessages = [];
        }

        $errorMessages[] = $snippets->get($message);

        $view->assign('sErrorMessages', $errorMessages);
        $view->assign('error_messages', $errorMessages);
    }
}
