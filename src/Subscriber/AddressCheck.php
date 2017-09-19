<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Shopware\Plugins\MoptAvalara\Form\FormCreator;
use Avalara\AddressLocationInfo;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\AddressFactory;

/**
 * Description of Checkout
 *
 */
class AddressCheck extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     * 
     * @return array
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
     * @param \Shopware\Plugins\MoptAvalara\Subscriber\Enlight_Event_EventArgs $args
     */
    public function onBeforeCheckoutConfirm(\Enlight_Event_EventArgs $args)
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = $this->getContainer()->get('session');
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
            if (empty($activeShippingAddressId = $session->offsetGet('checkoutShippingAddressId', null))) {
                $activeShippingAddressId = $userData['additional']['user']['default_shipping_address_id'];
            }
            if ($changes = $service->getAddressChanges($address, $response)) {
                $args->getSubject()->forward('edit', 'address', null, [
                    'MoptAvalaraAddressChanges' => $changes,
                    'sTarget' => 'checkout', 
                    'id' => $activeShippingAddressId
                ]);
                
                return true;
            }
        } catch (\GuzzleHttp\Exception\TransferException $e) {
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
        /*@var $session Enlight_Components_Session_Namespace */
        $session = $this->getContainer()->get('session');
        
        if (!$this->isCountryForDelivery($address->country)) {
            return false;
        }
        
        if(!$session->MoptAvalaraCheckedAddress) {
            return true;
        }
        
        if($session->MoptAvalaraCheckedAddress != $this->getAddressHash($address)) {
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
            ->getPluginConfig(FormCreator::ADDRESS_VALIDATION_COUNTRIES_FIELD)
        ;

        switch ($countriesForDelivery) {
            case FormCreator::DELIVERY_COUNTRY_NO_VALIDATION:
                return false;
            case FormCreator::DELIVERY_COUNTRY_USA:
                if($country == AddressFactory::COUNTRY_CODE__US) {
                    return true;
                }
                break;
            case FormCreator::DELIVERY_COUNTRY_CANADA:
                if($country == AddressFactory::COUNTRY_CODE__CA) {
                    return true;
                }
                break;
            case FormCreator::DELIVERY_COUNTRY_USA_AND_CANADA:
                $usaAndCanada = [
                    AddressFactory::COUNTRY_CODE__CA,
                    AddressFactory::COUNTRY_CODE__US
                ];
                
                if(in_array($country, $usaAndCanada, true)) {
                    return true;
                }
                break;
        }
        
        return false;
    }
    
    /**
     * get hash of given address
     * @param \Avalara\AddressLocationInfo $address
     */
    protected function getAddressHash(AddressLocationInfo $address) {
        return md5(serialize($address));
    }

    /**
     * show normalized shipping address
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

        $this->addErrorMessage($view);
    }

    /**
     * add error message
     * 
     * @param \Shopware\Plugins\MoptAvalara\Subscriber\Enlight_View_Default $view
     */
    protected function addErrorMessage(\Enlight_View_Default $view)
    {
        $snippets = $this->getContainer()->get('snippets')->getNamespace('frontend/MoptAvalara/messages');


        $errorMessages = $view->getAssign('sErrorMessages');
        if (!is_array($errorMessages)) {
            $errorMessages = [];
        }

        $errorMessages[] = $snippets->get('shippingAddressChangesFound');

        $view->assign('sErrorMessages', $errorMessages);
    }
}
