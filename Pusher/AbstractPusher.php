<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;

abstract class AbstractPusher implements PusherInterface
{
    /**
     * @var MessageSerializer
     */
    protected $serializer;

    /**
     * @var WampRouter
     */
    protected $router;

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param array|string $data
     */
    public function push($data, string $routeName, array $routeParameters = [], array $context = []): void
    {
        $channel = $this->router->generate($routeName, $routeParameters);
        $message = new Message($channel, $data);

        $this->doPush($this->serializer->serialize($message), $context);
    }

    /**
     * @param string|array $data
     */
    abstract protected function doPush($data, array $context): void;

    public function setSerializer(MessageSerializer $serializer): void
    {
        $this->serializer = $serializer;
    }

    public function setRouter(WampRouter $router): void
    {
        $this->router = $router;
    }

    public function setConnected($bool = true): void
    {
        $this->connected = $bool;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }
}
