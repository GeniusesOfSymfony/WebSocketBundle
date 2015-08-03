<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

class ServerPushHandlerRegistry
{
    /** @var array ServerPushHandlerInterface[] */
    protected $pushHandlers;

    public function __construct()
    {
        $this->pushHandlers = [];
    }

    /**
     * @param ServerPushHandlerInterface $handler
     * @param string                     $name
     */
    public function addPushHandler(ServerPushHandlerInterface $handler, $name)
    {
        $this->pushHandlers[$name] = $handler;
    }

    /**
     * @param $name
     *
     * @return ServerPushHandlerInterface
     */
    public function getPushHandler($name)
    {
        return $this->pushHandlers[$name];
    }

    /**
     * @return ServerPushHandlerInterface[]
     */
    public function getPushers()
    {
        return $this->pushHandlers;
    }
}
