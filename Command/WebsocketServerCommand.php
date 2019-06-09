<?php

namespace Gos\Bundle\WebSocketBundle\Command;

use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class WebsocketServerCommand extends Command
{
    protected static $defaultName = 'gos:websocket:server';

    /**
     * @var EntryPoint
     */
    protected $entryPoint;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    public function __construct(EntryPoint $entryPoint, string $host, int $port)
    {
        parent::__construct();

        $this->entryPoint = $entryPoint;
        $this->port = $port;
        $this->host = $host;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Starts the websocket server')
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the server to start, launches the first registered server if not specified')
            ->addOption('profile', 'm', InputOption::VALUE_NONE, 'Enable profiling of the server')
            ->addOption('host', 'a', InputOption::VALUE_OPTIONAL, 'The hostname of the websocket server')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port of the websocket server');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->entryPoint->launch(
            $input->getArgument('name'),
            $input->getOption('host') === null ? $this->host : $input->getOption('host'),
            $input->getOption('port') === null ? $this->port : $input->getOption('port'),
            $input->getOption('profile')
        );
    }
}
