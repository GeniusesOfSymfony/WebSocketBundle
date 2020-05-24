<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', PusherRegistry::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class PusherRegistry
{
    /**
     * @var PusherInterface[]
     */
    private array $pushers = [];

    public function addPusher(PusherInterface $pusher): void
    {
        $this->pushers[$pusher->getName()] = $pusher;
    }

    /**
     * @throws \InvalidArgumentException if the requested pusher was not registered
     */
    public function getPusher(string $name): PusherInterface
    {
        if (!$this->hasPusher($name)) {
            throw new \InvalidArgumentException(sprintf('A pusher named "%s" has not been registered.', $name));
        }

        return $this->pushers[$name];
    }

    public function getPushers(): array
    {
        return $this->pushers;
    }

    public function hasPusher(string $name): bool
    {
        return isset($this->pushers[$name]);
    }
}
