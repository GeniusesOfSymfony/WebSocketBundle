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

    public function close();

    public function setName(string $name): void;

    /**
     * @return string
     */
    public function getName();
}
