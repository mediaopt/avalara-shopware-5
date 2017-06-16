<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

/**
 * Description of Checkout
 *
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
            'Enlight_Controller_Action_PostDispatch_Backend_Order_SavePosition' => 'onBackendOrderSavePosition',
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
        $view = $args->getSubject()->View();
        if (!$view->getAssign('success')) {
            return;
        }
        
        //prevent shop duplication http://forum.shopware.com/programmierung-f56/nach-email-versand-in-sorder-wird-main-shop-dupliziert-t21650.html
        Shopware()->Models()->clear();
        
        $orderId = $args->getSubject()->Request()->getParam('orderId', null);
        $order = $this->getOrderById($orderId);
        $order->getAttribute()->setMoptAvalaraOrderChanged(1);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
    
    /**
     * 
     * @param \Enlight_Event_EventArgs $args
     * @return void
     */
    protected function onPostDispatchSave(\Enlight_Event_EventArgs $args)
    {
        $view = $args->getSubject()->View();
        if (!$view->getAssign('success')) {
            return;
        }
        
        //prevent shop duplication http://forum.shopware.com/programmierung-f56/nach-email-versand-in-sorder-wird-main-shop-dupliziert-t21650.html
        Shopware()->Models()->clear();
        
        $orderId = $args->getSubject()->Request()->getParam('id', null);
        $order = $this->getOrderById($orderId);
        $order->getAttribute()->setMoptAvalaraOrderChanged(1);
        Shopware()->Models()->persist($order);
        Shopware()->Models()->flush();
    }
}
