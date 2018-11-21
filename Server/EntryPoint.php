<?php

namespace Gos\Bundle\WebSocketBundle\Server;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class EntryPoint
{
    /**
     * @var ServerRegistry
     */
    protected $serverRegistry;

    public function __construct(ServerRegistry $serverRegistry)
    {
        $this->serverRegistry = $serverRegistry;
    }

    /**
     * @param string $serverName
     * @param string $host
     * @param int    $port
     * @param bool   $profile
     */
    public function launch($serverName, $host, $port, $profile)
    {
        if (null === $serverName) {
            $servers = $this->serverRegistry->getServers();

            if (empty($servers)) {
                throw new \RuntimeException('There are no servers registered to launch.');
            }

            reset($servers);
            $server = current($servers);
        } else {
            if (!$this->serverRegistry->hasServer($serverName)) {
                throw new \RuntimeException(
                    sprintf(
                        'Unknown server %s, available servers are [ %s ]',
                        $serverName,
                        implode(', ', array_keys($this->serverRegistry->getServers()))
                    )
                );
            }

            $server = $this->serverRegistry->getServer($serverName);
        }

        $server->launch($host, $port, $profile);
    }
}
