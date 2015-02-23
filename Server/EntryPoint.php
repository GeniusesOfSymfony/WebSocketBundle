<?php

namespace Gos\Bundle\WebSocketBundle\Server;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ServerRegistry  $serverRegistry
     * @param LoggerInterface $logger
     */
    public function __construct(ServerRegistry $serverRegistry, LoggerInterface $logger = null)
    {
        $this->serverRegistry = $serverRegistry;
        $this->logger = $logger;
    }

    /**
     * Launches the relevant servers needed by Gos WebSocket.
     */
    public function launch($serverName)
    {
        $servers = $this->serverRegistry->getServers();

        if(null === $serverName){
            reset($servers);
            $server = current($servers);
        }else{
            if(!isset($servers[$serverName])){
                throw new \RuntimeException(sprintf(
                    'Unknown server %s in [%s]',
                    $serverName,
                    implode(', ', array_keys($servers))
                ));
            }

            $server = $servers[$serverName];
        }

        if (null !== $this->logger) {
            $this->logger->info(sprintf(
                'Launching %s on %s',
                $server->getName(),
                $server->getAddress()
            ));
        }

        $server->launch();
    }
}
