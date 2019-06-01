<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Registry;

use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class ServerRegistry
{
    /**
     * @var ServerInterface[]
     */
    protected $servers = [];

    public function addServer(ServerInterface $server)
    {
        $this->servers[$server->getName()] = $server;
    }

    /**
     * @return ServerInterface
     */
    public function getServer($serverName)
    {
        if (!$this->hasServer($serverName)) {
            throw new \InvalidArgumentException(sprintf('A server named "%s" has not been registered.', $serverName));
        }

        return $this->servers[$serverName];
    }

    /**
     * @return ServerInterface[]
     */
    public function getServers()
    {
        return $this->servers;
    }

    public function hasServer(string $serverName): bool
    {
        return isset($this->servers[$serverName]);
    }
}
