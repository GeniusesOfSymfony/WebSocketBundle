<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

class PusherRegistry
{
    /**
     * @var PusherInterface[]
     */
    protected $pushers = [];

    /**
     * @param PusherInterface $pusher
     */
    public function addPusher(PusherInterface $pusher)
    {
        $this->pushers[$pusher->getName()] = $pusher;
    }

    /**
     * @param $name
     *
     * @return PusherInterface
     */
    public function getPusher($name)
    {
        if (!$this->hasPusher($name)) {
            throw new \InvalidArgumentException(sprintf('A pusher named "%s" has not been registered.', $name));
        }

        return $this->pushers[$name];
    }

    /**
     * @return PusherInterface[]
     */
    public function getPushers()
    {
        return $this->pushers;
    }

    public function hasPusher(string $name): bool
    {
        return isset($this->pushers[$name]);
    }
}
