<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

interface PusherInterface
{
    /**
     * @param MessageInterface $data
     * @param string           $routeName
     * @param array[]          $routeParameters
     */
    public function push($data, $routeName, $routeParameters);

    /**
     * @return array
     */
    public function getConfig();

    public function close();
}
