<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

interface PusherInterface
{
    /**
     * @param string|array $data
     * @param string       $routeName
     * @param array[]      $routeParameters
     */
    public function push($data, $routeName, Array $routeParameters = array(), Array $context = []);

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @param array $config
     */
    public function setConfig($config);

    public function close();

    /**
     * @return string
     */
    public function getName();
}
