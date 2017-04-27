<?php

namespace Shopware\Plugins\MoptAvalara\Subscriber;

use Enlight\Event\SubscriberInterface;

/**
 * Description of AbstractSubscriber
 *
 */
abstract class AbstractSubscriber implements SubscriberInterface
{
    protected $bootstrap;
    
    public function __construct($bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }
    
    abstract public static function getSubscribedEvents();
    
    public function getBootstrap()
    {
        return $this->bootstrap;
    }
}
