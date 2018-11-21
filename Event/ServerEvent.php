<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;
use Symfony\Component\EventDispatcher\Event;

class ServerEvent extends Event
{
    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Server
     */
    protected $server;

    public function __construct(LoopInterface $loop, ServerInterface $server)
    {
        $this->loop = $loop;
        $this->server = $server;
    }

    /**
     * @return LoopInterface
     */
    public function getEventLoop()
    {
        return $this->loop;
    }

    /**
     * @return ServerInterface
     */
    public function getServer()
    {
        return $this->server;
    }
}
