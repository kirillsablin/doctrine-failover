<?php
namespace FailoverContext;

use Doctrine\Common\EventSubscriber;

class EventSubscriberForTests implements EventSubscriber
{
    public $onFailoverInvoked = 0;
    public $onFailbackInvoked = 0;

    function getSubscribedEvents()
    {
        return array('onFailover', 'onFailback');
    }

    public function onFailover()
    {
        $this->onFailoverInvoked++;
    }

    public function onFailback()
    {
        $this->onFailbackInvoked++;
    }

}