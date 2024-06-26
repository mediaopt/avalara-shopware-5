<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Shopware\Components\Theme\LessDefinition;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Subscriber
 */
class TemplatingSubscriber extends AbstractSubscriber
{

    /**
     * return array with all subsribed events
     *
     * @return string[]
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend' => 'onPostDispatchFrontend',
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onPostDispatchBackendOrder',
            'Theme_Compiler_Collect_Plugin_Less' => 'addLessFiles',
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
            $view->extendsTemplate('Backend/order/model/mopt_avalara__billing_attribute.js');
            $view->extendsTemplate('Backend/order/model/mopt_avalara__attribute.js');
        }
    }
    
    /**
     * Provide the file collection for less
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function addLessFiles()
    {
        $less = new LessDefinition(
            [],
            [$this->getBootstrap()->Path() . '/Views/frontend/_public/src/less/all.less'],
            $this->getBootstrap()->Path()
        );

        return new ArrayCollection([$less]);
    }
}
