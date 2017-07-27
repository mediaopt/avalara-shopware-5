<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

/**
 * 
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class BackendAttributesSubscriber extends AbstractSubscriber
{

    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Category' => 'onPostDispatchBackendCategory',
            'Enlight_Controller_Action_PostDispatch_Backend_Article' => 'onPostDispatchBackendArticle',
            'Enlight_Controller_Action_PostDispatch_Backend_Shipping' => 'onPostDispatchBackendShipping',
        ];
    }

    /**
     * extend backend category editing
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendCategory(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->getBootstrap()->Path() . 'Views/');
        if ($args->getRequest()->getActionName() === 'load') {
            $args->getSubject()->View()->extendsTemplate('Backend/category/model/attribute.js');
            $args->getSubject()->View()->extendsTemplate('Backend/category/tabs/settings.js');
        }
    }

    /**
     *  extend backend article editing
     *
     * @param Enlight_Event_EventArgs $args
     */
    public function onPostDispatchBackendArticle(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->getBootstrap()->Path() . 'Views/');
        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('Backend/article/model/attribute.js');
            $view->extendsTemplate('Backend/article/tabs/settings.js');
        }
    }

    public function onPostDispatchBackendShipping(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        $view->addTemplateDir($this->getBootstrap()->Path() . 'Views/');
        if ($args->getRequest()->getActionName() === 'load') {
            $view->extendsTemplate('Backend/shipping/view/edit/default/mopt_avalara__form_left.js');
            $view->extendsTemplate('Backend/shipping/model/mopt_avalara__attribute.js');
        }
    }
}
