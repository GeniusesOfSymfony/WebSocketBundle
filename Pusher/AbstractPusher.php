<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractPusher implements PusherInterface
{
    /**
     * @var SerializerInterface
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

    public function __construct(WampRouter $router, SerializerInterface $serializer)
    {
        $this->router = $router;
        $this->serializer = $serializer;
    }

    /**
     * @param array|string $data
     */
    public function push($data, string $routeName, array $routeParameters = [], array $context = []): void
    {
        $channel = $this->router->generate($routeName, $routeParameters);

        $this->doPush(new Message($channel, $data), $context);
    }

    abstract protected function doPush(Message $message, array $context): void;

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function setConnected(bool $bool = true): void
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
}
