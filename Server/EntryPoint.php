<?php

namespace Gos\Bundle\WebSocketBundle\Server;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class EntryPoint
{
    /**
     * @var ServerInterface[]
     */
    protected $serverRegistry;

    /**
     * @param ServerRegistry $serverRegistry
     */
    public function __construct(ServerRegistry $serverRegistry)
    {
        $this->serverRegistry = $serverRegistry;
    }

    /**
     * @param string $serverName
     * @param bool   $profile
     */
    public function launch($serverName, $host, $port, $profile)
    {
        $servers = $this->serverRegistry->getServers();

        if (null === $serverName) {
            reset($servers);
            $server = current($servers);
        } else {
            if (!isset($servers[$serverName])) {
                throw new \RuntimeException(sprintf(
                    'Unknown server %s in [%s]',
                    $serverName,
                    implode(', ', array_keys($servers))
                ));
            }

            $server = $servers[$serverName];
        }

        $server->launch($host, $port, $profile);
    }
}
