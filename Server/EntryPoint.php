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
    public function launch()
    {
        /** @var ServerInterface $server */
        foreach ($this->serverRegistry->getServers() as $server) {
            if (null !== $this->logger) {
                $this->logger->info(sprintf(
                    'Launching %s on %s',
                    $server->getName(),
                    $server->getAddress()
                ));
            }

            //launch server into background process?
            $server->launch();
        }
    }
}
