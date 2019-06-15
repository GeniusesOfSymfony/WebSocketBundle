<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Command;

use Gos\Bundle\WebSocketBundle\Server\ServerLauncherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class WebsocketServerCommand extends Command
{
    protected static $defaultName = 'gos:websocket:server';

    /**
     * @var ServerLauncherInterface
     */
    private $serverLauncher;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    public function __construct(ServerLauncherInterface $entryPoint, string $host, int $port)
    {
        parent::__construct();

        $this->serverLauncher = $entryPoint;
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
        $this->serverLauncher->launch(
            $input->getArgument('name'),
            null === $input->getOption('host') ? $this->host : $input->getOption('host'),
            null === $input->getOption('port') ? $this->port : $input->getOption('port'),
            $input->getOption('profile')
        );
    }
}
