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
class BackendOrderUpdateSubscriber extends AbstractSubscriber
{
    /**
     * return array with all subsribed events
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Order' => 'onPostDispatchBackendOrder',
        ];
    }
    
    /**
     * 
     * @param \Enlight_Event_EventArgs $args
     * @return void
     */
    public function onPostDispatchBackendOrder(\Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        $observedActions = ['save', 'savePosition'];
        $action = $request->getActionName();
        
        if (!in_array($action, $observedActions)) {
            return;
        }
        
        $fnc = 'onPostDispatch' . ucfirst($action);
        $this->$fnc($args);
    }
    
    /**
     * 
     * @param \Enlight_Event_EventArgs $args
     * @return void
     */
    protected function onPostDispatchSavePosition(\Enlight_Event_EventArgs $args)
    {
        $orderId = $args->getSubject()->Request()->getParam('orderId', null);
        $this->updateOrderChanged($args, $orderId);
    }
    
    /**
     * 
     * @param \Enlight_Event_EventArgs $args
     * @return void
     */
    protected function onPostDispatchSave(\Enlight_Event_EventArgs $args)
    {
        $orderId = $args->getSubject()->Request()->getParam('id', null);
        $this->updateOrderChanged($args, $orderId);
    }
    
    /**
     * 
     * @param \Enlight_Event_EventArgs $args
     * @param int $orderId
     * @return null
     */
    private function updateOrderChanged(\Enlight_Event_EventArgs $args, $orderId)
    {
        $adapter = $this->getAdapter();
        $view = $args->getSubject()->View();
        if (!$view->getAssign('success')) {
            return;
        }
        
        //prevents shop duplication http://forum.shopware.com/programmierung-f56/nach-email-versand-in-sorder-wird-main-shop-dupliziert-t21650.html
        Shopware()->Models()->clear();
        $order = $adapter->getOrderById($orderId);
        $order->getAttribute()->setMoptAvalaraOrderChanged(1);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
}
