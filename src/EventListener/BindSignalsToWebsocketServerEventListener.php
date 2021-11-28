<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\EventListener;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\TimerInterface;

/**
 * @internal
 */
final class BindSignalsToWebsocketServerEventListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private PeriodicRegistry $periodicRegistry,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(ServerLaunchedEvent $event): void
    {
        $loop = $event->getEventLoop();
        $server = $event->getServer();

        $closer = function () use ($server, $loop): void {
            $this->logger?->notice('Stopping server ...');

            $server->emit('end');
            $server->close();

            foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
                if ($periodic instanceof TimerInterface) {
                    $loop->cancelTimer($periodic);
                }
            }

            $loop->stop();

            $this->tokenStorage->removeAllTokens();

            $this->logger?->notice('Server stopped!');
        };

        if (\defined('SIGINT')) {
            $loop->addSignal(\SIGINT, $closer);
        }

        if (\defined('SIGTERM')) {
            $loop->addSignal(\SIGTERM, $closer);
        }
    }
}
