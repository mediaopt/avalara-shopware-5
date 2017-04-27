<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

/**
 * Description of Checkout
 *
 */
class AddressCheck extends AbstractSubscriber
{

    /**
     * DI-container
     *
     * @var \Shopware\Components\Form\Container\
     */
    protected $container;

    /**
     * @param \Shopware_Plugins_Core_MoptIngenico_Bootstrap $bootstrap
     * @param \Enlight_Config $pluginConfig
     */
    public function __construct($bootstrap, $container)
    {
        parent::__construct($bootstrap);
        $this->container = $container;
    }

    /**
     * return array with all subsribed events
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'Enlight_Controller_Action_Frontend_Checkout_Confirm' => 'onBeforeCheckoutConfirm',
            'Enlight_Controller_Action_PostDispatch_Frontend_Address' => 'onPostDispatchFrontendAddress',
        );
    }

    /**
     * perform address check 
     * @param \Shopware\Plugins\MoptAvalara\Subscriber\Enlight_Event_EventArgs $args
     */
    public function onBeforeCheckoutConfirm(\Enlight_Event_EventArgs $args)
    {
        /* @var $session Enlight_Components_Session_Namespace */
        $session = $this->container->get('session');
        /* @var $sdkMain \Mediaopt\Avalara\Sdk\Main */
        $sdkMain = $this->container->get('MediaoptAvalaraSdkMain');

        $address = $sdkMain->getAdapter()->getFactory('Address')->buildDeliveryAddress();

        if (!$this->isAddressToBeValidated($address) || empty($args->getSubject()->View()->sUserLoggedIn)) {
            $sdkMain->getLogger()->info('address is not to be validated.');
            return;
        }
        try {
            $userData = $args->getSubject()->View()->sUserData;
            $sdkMain->getLogger()->info('validating address.');
            /* @var $service \Mediaopt\Avalara\Sdk\Service\ValidateAddress */
            $service = $sdkMain->getService('validateAddress');
            
            $response = $service->validate($address);
            $session->MoptAvalaraCheckedAddress = $this->getAddressHash($address);

            if (empty($activeShippingAddressId = $session->offsetGet('checkoutShippingAddressId', null))) {
                $activeShippingAddressId = $userData['additional']['user']['default_shipping_address_id'];
            }
            if ($changes = $service->getAddressChanges($address, $response)) {
                $args->getSubject()->forward('edit', 'address', null, array('MoptAvalaraAddressChanges' => $changes,
                    'sTarget' => 'checkout', 'id' => $activeShippingAddressId));
                return true;
            }

        } catch (\GuzzleHttp\Exception\TransferException $e) {
            //address check failed - nothing to do
            $sdkMain->getLogger()->info('address check failed.');
        }
    }

    /**
     * check if address has aready been validated
     * @param \Mediaopt\Avalara\Sdk\Model\Address $address
     * @return boolean
     */
    protected function isAddressToBeValidated(\Mediaopt\Avalara\Sdk\Model\Address $address)
    {
        /*@var $session Enlight_Components_Session_Namespace */
        $session = $this->container->get('session');
        
        //country check
        $pluginConfig = Shopware()->Plugins()->Backend()->MoptAvalara()->Config();
        
        switch ($pluginConfig->mopt_avalara_addressvalidation_countries) {
            case 1: //no validation
                return false;
            case 2: //US
                if($address->getCountry() != \Mediaopt\Avalara\Sdk\Model\Address::COUNTRY_CODE__US) {
                    return false;
                }
                break;
            case 3: //CA
                if($address->getCountry() != \Mediaopt\Avalara\Sdk\Model\Address::COUNTRY_CODE__CA) {
                    return false;
                }
                break;
            case 4: //US & CA
                if(!in_array($address->getCountry(), array(
                    \Mediaopt\Avalara\Sdk\Model\Address::COUNTRY_CODE__CA,
                    \Mediaopt\Avalara\Sdk\Model\Address::COUNTRY_CODE__US))) {
                    return false;
                }
                break;
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
     * get hash of given address
     * @param \Mediaopt\Avalara\Sdk\Model\Address $address
     */
    protected function getAddressHash(\Mediaopt\Avalara\Sdk\Model\Address $address) {
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
                case 'City':
                    $formData['city'] = $value;
                    break;
                case 'Line1':
                    $formData['street'] = $value;
                    break;
                case 'PostalCode':
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
        $snippets = $this->container->get('snippets')->getNamespace('frontend/MoptAvalara/messages');


        $errorMessages = $view->getAssign('sErrorMessages');
        if (!is_array($errorMessages)) {
            $errorMessages = array();
        }

        $errorMessages[] = $snippets->get('shippingAddressChangesFound');

        $view->assign('sErrorMessages', $errorMessages);
    }
}
