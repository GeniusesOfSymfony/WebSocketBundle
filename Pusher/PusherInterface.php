<?php

namespace Gos\Bundle\WebSocketBundle\Pusher;

interface PusherInterface
{
    /**
     * @param string|array $data
     * @param string       $routeName
     * @param array        $routeParameters
     * @param array        $context
     */
    public function push($data, $routeName, array $routeParameters = [], array $context = []);

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
