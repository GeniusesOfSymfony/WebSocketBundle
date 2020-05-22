<?php

namespace Gos\Bundle\WebSocketBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

trigger_deprecation('gos/web-socket-bundle', '1.1', 'The %s class is deprecated will be removed in 2.0. Use the %s class instead.', ServerCommand::class, WebsocketServerCommand::class);

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

        return parent::execute($input, $output);
    }
}
