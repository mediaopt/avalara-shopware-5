<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

/**
 * Description of Checkout
 *
 */
class TemplatingSubscriber extends AbstractSubscriber
{

    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend' => 'onPostDispatchFrontend',
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onPostDispatchBackendOrder',
        ];
    }
    
    /**
     * register CSS
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchFrontend(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->getBootstrap()->Path() . 'Views/');
        $view->extendsTemplate('frontend/index/mopt_avalara__header.tpl');
    }
    
    /**
     * register CSS
     * @param \Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendOrder(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->getBootstrap()->Path() . 'Views/');
        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('Backend/order/tabs/avalara_tab.js');
            $view->extendsTemplate('Backend/order/view/list/mopt_avalara__list.js');
            $view->extendsTemplate('Backend/order/model/billing_attribute.js');
            $view->extendsTemplate('Backend/order/model/mopt_avalara__attribute.js');
        }
    }
}
