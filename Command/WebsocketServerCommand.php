<?php

namespace Gos\Bundle\WebSocketBundle\Command;

use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketServerCommand extends Command
{
    /**
     * @var EntryPoint
     */
    protected $entryPoint;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @param EntryPoint      $entryPoint
     * @param string          $host
     * @param int             $port
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntryPoint $entryPoint,
        $host,
        $port,
        LoggerInterface $logger = null
    ) {
        $this->entryPoint = $entryPoint;
        $this->port = (int) $port;
        $this->host = $host;
        $this->logger = null === $logger ? new NullLogger() : $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('gos:websocket:server')
            ->setDescription('Starts the web socket servers')
            ->addArgument('name', InputArgument::OPTIONAL, 'Server name')
            ->addOption('profile', 'm', InputOption::VALUE_NONE, 'Profiling server')
            ->addOption('host', 'a', InputOption::VALUE_OPTIONAL, 'Host')
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'port')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entryPoint->launch(
            $input->getArgument('name'),
            $input->getOption('host') === null ? $this->host : $input->getOption('host'),
            $input->getOption('port') === null ? $this->port : $input->getOption('port'),
            $input->getOption('profile')
        );
    }
}
