<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

/**
 * Description of Checkout
 *
 */
class Shipping extends AbstractSubscriber
{

    /**
     * return array with all subsribed events
     * 
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Shipping' => 'onPostDispatchBackendShipping',
        ];
    
    }
    
    public function onPostDispatchBackendShipping(\Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        $observedActions = ['updateDispatch', 'createDispatch'];
        $action = $request->getActionName();
        
        if (!in_array($action, $observedActions)) {
            return;
        }
        
        $view = $args->getSubject()->View();
        if (!$view->getAssign('success')) {
            return;
        }
        
        $data = $view->getAssign('data');
        $attributes = $request->get('attribute')[0];
        $moptAvalaraTaxcode = $attributes['moptAvalaraTaxcode'];
        $respository = Shopware()->Models()->getRepository('Shopware\Models\Dispatch\Dispatch');
        /*@var $order \Shopware\Models\Order\Order */
        $dispatch = $respository->find($data['id']);
        $dispatch->getAttribute()->setMoptAvalaraTaxcode($moptAvalaraTaxcode);
        Shopware()->Models()->persist($dispatch);
        Shopware()->Models()->flush();
    }
}