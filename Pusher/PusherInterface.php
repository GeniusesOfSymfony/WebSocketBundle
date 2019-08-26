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
     *
     * @deprecated to be removed in 2.0. Configuration will no longer be automatically injected in pushers.
     */
    public function getConfig();

    /**
     * @param array $config
     *
     * @deprecated to be removed in 2.0. Configuration will no longer be automatically injected in pushers.
     */
    public function setConfig($config);

    public function close();

    /**
     * @return string
     */
    public function getName();
}
