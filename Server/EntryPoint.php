<?php

namespace Gos\Bundle\WebSocketBundle\Server;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
     * @var OutputInterface
     */
    protected $output;

    /**
     * @param ServerRegistry $serverRegistry
     */
    public function __construct(ServerRegistry $serverRegistry)
    {
        $this->serverRegistry = $serverRegistry;
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output = null)
    {
        $this->output = $output;
    }

    /**
     * @return OutputInterface|null
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Launches the relevant servers needed by Gos WebSocket.
     */
    public function launch()
    {
        /** @var ServerInterface $server */
        foreach ($this->serverRegistry->getServers() as $server) {
            if (null !== $this->output) {
                $this->getOutput()->writeln("Launching " . $server->getName() . " on: " . $server->getAddress());
            }

            //launch server into background process?
            $server->launch();
        }
    }
}
