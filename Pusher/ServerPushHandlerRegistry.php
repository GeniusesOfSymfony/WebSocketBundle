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
        if (!$this->hasPushHandler($name)) {
            throw new \InvalidArgumentException(sprintf('A push handler named "%s" has not been registered.', $name));
        }

        return $this->pushHandlers[$name];
    }

    /**
     * @return ServerPushHandlerInterface[]
     */
    public function getPushers()
    {
        return $this->pushHandlers;
    }

    public function hasPushHandler(string $name): bool
    {
        return isset($this->pushHandlers[$name]);
    }
}
