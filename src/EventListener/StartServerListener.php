<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\TimerInterface;

final class StartServerListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private PeriodicRegistry $periodicRegistry;
    private ServerPushHandlerRegistry $serverPushHandlerRegistry;

    public function __construct(
        PeriodicRegistry $periodicRegistry,
        ServerPushHandlerRegistry $serverPushHandlerRegistry
    ) {
        $this->periodicRegistry = $periodicRegistry;
        $this->serverPushHandlerRegistry = $serverPushHandlerRegistry;
    }

    public function bindPnctlEvent(ServerLaunchedEvent $event): void
    {
        $loop = $event->getEventLoop();
        $server = $event->getServer();

        $closer = function () use ($server, $loop): void {
            if (null !== $this->logger) {
                $this->logger->notice('Stopping server ...');
            }

            foreach ($this->serverPushHandlerRegistry->getPushers() as $handler) {
                $handler->close();

                if (null !== $this->logger) {
                    $this->logger->info(sprintf('Stop %s push handler', $handler->getName()));
                }
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
        };

        if (\defined('SIGINT')) {
            $loop->addSignal(\SIGINT, $closer);
        }

        if (\defined('SIGTERM')) {
            $loop->addSignal(\SIGTERM, $closer);
        }
    }
}
