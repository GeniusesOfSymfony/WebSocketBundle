<?php

namespace Gos\Bundle\WebSocketBundle\Server\Type;

interface ServerInterface
{
    /**
     * Launches the server loop.
     */
    public function launch($host, $port, $profile);

    /**
     * Returns a string of the name of the server/service for debugging / display purposes.
     *
     * @return string
     */
    public function getName();
}
