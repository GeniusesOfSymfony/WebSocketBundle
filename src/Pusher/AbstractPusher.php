<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Symfony\Component\Serializer\SerializerInterface;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', AbstractPusher::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
abstract class AbstractPusher implements PusherInterface
{
    protected SerializerInterface $serializer;
    protected WampRouter $router;
    protected bool $connected = false;
    protected string $name = '';

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

        if (\is_string($data)) {
            $data = [$data];
        } elseif (!\is_array($data)) {
            throw new \InvalidArgumentException(sprintf('The $data argument of %s() must be a string or array, a %s was given.', __METHOD__, \gettype($data)));
        }

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
