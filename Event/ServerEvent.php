<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\Event;

class ServerEvent extends Event
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Get Server Event Loop to add other services in the same loop.
     *
     * @return LoopInterface
     */
    public function getEventLoop()
    {
        return $this->loop;
    }
}
