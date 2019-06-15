<?php declare(strict_types=1);

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
    private $servers = [];

    public function addServer(ServerInterface $server): void
    {
        $this->servers[$server->getName()] = $server;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getServer(string $serverName): ServerInterface
    {
        if (!$this->hasServer($serverName)) {
            throw new \InvalidArgumentException(sprintf('A server named "%s" has not been registered.', $serverName));
        }

        return $this->servers[$serverName];
    }

    /**
     * @return ServerInterface[]
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    public function hasServer(string $serverName): bool
    {
        return isset($this->servers[$serverName]);
    }
}
