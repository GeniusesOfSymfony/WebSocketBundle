<?php

namespace Gos\Bundle\WebSocketBundle\Command;

use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class ServerCommand extends Command
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
     * @param EntryPoint      $entryPoint
     * @param LoggerInterface $logger
     */
    public function __construct(EntryPoint $entryPoint, LoggerInterface $logger = null)
    {
        $this->entryPoint = $entryPoint;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('gos:server')
            ->setDescription('Starts the web socket servers')
            ->addArgument('name', InputArgument::OPTIONAL, 'Server name')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->entryPoint->launch($input->getArgument('name'));
    }
}
