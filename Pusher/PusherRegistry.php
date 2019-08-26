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
     * @param string          $name   {@deprecated}
     */
    public function addPusher(PusherInterface $pusher, $name)
    {
        @trigger_error(
            sprintf('The $name argument of %s() is deprecated will be removed in 2.0. The name will be extracted from the $pusher instead.', __METHOD__),
            E_USER_DEPRECATED
        );

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
