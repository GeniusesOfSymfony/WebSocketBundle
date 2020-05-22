<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

class ServerPushHandlerRegistry
{
    /**
     * @var array ServerPushHandlerInterface[]
     */
    protected $pushHandlers = [];

    /**
     * @param ServerPushHandlerInterface $handler
     * @param string                     $name    {@deprecated}
     */
    public function addPushHandler(ServerPushHandlerInterface $handler, $name)
    {
        trigger_deprecation('gos/web-socket-bundle', '1.9', 'The $name argument of %s() is deprecated will be removed in 2.0. The name will be extracted from the pusher instead.', __METHOD__);

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
