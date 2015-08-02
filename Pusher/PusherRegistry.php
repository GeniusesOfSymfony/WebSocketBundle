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
     */
    public function addPusher(PusherInterface $pusher)
    {
        $config = $pusher->getConfig();
        $this->pushers[$config['type']] = $pusher;
    }

    /**
     * @return array|PusherInterface[]
     */
    public function getPushers()
    {
        return $this->pushers;
    }
}
