<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', ServerPushHandlerRegistry::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class ServerPushHandlerRegistry
{
    /**
     * @var ServerPushHandlerInterface[]
     */
    private array $pushHandlers = [];

    public function addPushHandler(ServerPushHandlerInterface $handler): void
    {
        $this->pushHandlers[$handler->getName()] = $handler;
    }

    /**
     * @throws \InvalidArgumentException if the requested push handler was not registered
     */
    public function getPushHandler(string $name): ServerPushHandlerInterface
    {
        if (!$this->hasPushHandler($name)) {
            throw new \InvalidArgumentException(sprintf('A push handler named "%s" has not been registered.', $name));
        }

        return $this->pushHandlers[$name];
    }

    /**
     * @return ServerPushHandlerInterface[]
     */
    public function getPushers(): array
    {
        return $this->pushHandlers;
    }

    public function hasPushHandler(string $name): bool
    {
        return isset($this->pushHandlers[$name]);
    }
}
