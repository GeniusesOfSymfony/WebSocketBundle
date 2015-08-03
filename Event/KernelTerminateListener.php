<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class KernelTerminateListener
{
    /**
     * @var PusherRegistry
     */
    protected $pusherRegistry;

    public function __construct(PusherRegistry $pusherRegistry)
    {
        $this->pusherRegistry = $pusherRegistry;
    }

    /**
     * @param PostResponseEvent $event
     */
    public function closeConnection(PostResponseEvent $event)
    {
        foreach ($this->pusherRegistry->getPushers() as $pusher) {
            $pusher->close();
        }
    }
}
