<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\TimerInterface;

final class StartServerListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private PeriodicRegistry $periodicRegistry;

    public function __construct(PeriodicRegistry $periodicRegistry)
    {
        $this->periodicRegistry = $periodicRegistry;
    }

    public function __invoke(ServerLaunchedEvent $event): void
    {
        if (\defined('SIGINT')) {
            $loop = $event->getEventLoop();
            $server = $event->getServer();

            $loop->addSignal(
                SIGINT,
                function () use ($server, $loop): void {
                    if (null !== $this->logger) {
                        $this->logger->notice('Stopping server ...');
                    }

                    $server->emit('end');
                    $server->close();

                    foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
                        if ($periodic instanceof TimerInterface) {
                            $loop->cancelTimer($periodic);
                        }
                    }

                    $loop->stop();

                    if (null !== $this->logger) {
                        $this->logger->notice('Server stopped!');
                    }
                }
            );
        }
    }
}
