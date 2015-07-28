<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class KernelTerminateListener
{
    /**
     * @var PusherInterface
     */
    protected $pusher;

    /**
     * @param PusherInterface $pusher
     */
    public function __construct(PusherInterface $pusher)
    {
        $this->pusher = $pusher;
    }

    /**
     * @param PostResponseEvent $event
     */
    public function closeConnection(PostResponseEvent $event)
    {
        $this->pusher->close();
    }
}
