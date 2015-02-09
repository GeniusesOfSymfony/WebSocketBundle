<?php

namespace Gos\Bundle\WebSocketBundle\Command;

use Gos\Bundle\WebSocketBundle\Server\EntryPoint;
use Symfony\Component\Console\Command\Command;
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
     * @param EntryPoint $entryPoint
     */
    public function __construct(EntryPoint $entryPoint)
    {
        $this->entryPoint = $entryPoint;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('gos:server')
            ->setDescription('Starts the web socket servers');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("Starting web socket");
        $this->entryPoint->setOutput($output);
        $this->entryPoint->launch();
    }
}
