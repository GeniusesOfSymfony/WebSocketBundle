<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ServerRegistry
{
    /**
     * @var ServerInterface[]
     */
    protected $servers;

    public function __construct()
    {
        $this->servers = [];
    }

    /**
     * @param ServerInterface $server
     */
    public function addServer(ServerInterface $server)
    {
        $this->servers[$server->getName()] = $server;
    }

    /**
     * @param $serverName
     *
     * @return ServerInterface
     */
    public function getServer($serverName)
    {
        return $this->servers[$serverName];
    }

    /**
     * @return ServerInterface[]
     */
    public function getServers()
    {
        return $this->servers;
    }
}
