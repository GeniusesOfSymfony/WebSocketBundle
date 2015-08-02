<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

class NullPusher implements PusherInterface
{
    /**
     * @param MessageInterface $data
     * @param string           $routeName
     * @param array[]          $routeParameters
     */
    public function push($data, $routeName, $routeParameters)
    {
        // stub
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        // stub
    }

    public function close()
    {
        // stub
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        // TODO: Implement setConfig() method.
    }
}
