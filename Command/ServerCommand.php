<?php

namespace Gos\Bundle\WebSocketBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 *
 * @deprecated Will be removed in v2 use WebsocketServerCommand instead
 */
class ServerCommand extends WebsocketServerCommand
{
    protected function configure()
    {
        $this
            ->setName('gos:server')
            ->setDescription('Starts the web socket servers')
            ->addArgument('name', InputArgument::OPTIONAL, 'Server name')
            ->addOption('profile', 'p', InputOption::VALUE_NONE, 'Profiling server');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<error>This command is deprecated and will be removed in v2, use gos:websocket:server instead</error>');
        parent::execute($input, $output);
    }
}
