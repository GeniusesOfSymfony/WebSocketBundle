<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

class PusherRegistry
{
    /** @var PusherInterface[] */
    protected $pushers;

    public function __construct()
    {
        $this->pushers = [];
    }

    /**
     * @param PusherInterface $pusher
     * @param string          $name
     */
    public function addPusher(PusherInterface $pusher, $name)
    {
        $this->pushers[$name] = $pusher;
    }

    /**
     * @param $name
     *
     * @return PusherInterface
     */
    public function getPusher($name)
    {
        return $this->pushers[$name];
    }

    /**
     * @return array|PusherInterface[]
     */
    public function getPushers()
    {
        return $this->pushers;
    }
}
