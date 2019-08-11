<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher;

final class PusherRegistry
{
    /**
     * @var PusherInterface[]
     */
    private $pushers = [];

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
