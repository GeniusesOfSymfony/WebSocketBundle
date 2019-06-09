<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use React\EventLoop\TimerInterface;

class StartServerListener implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var PeriodicRegistry
     */
    protected $periodicRegistry;

    /**
     * @var ServerPushHandlerRegistry
     */
    protected $serverPushHandlerRegistry;

    public function __construct(
        PeriodicRegistry $periodicRegistry,
        ServerPushHandlerRegistry $serverPushHandlerRegistry
    ) {
        $this->periodicRegistry = $periodicRegistry;
        $this->serverPushHandlerRegistry = $serverPushHandlerRegistry;
    }

    public function bindPnctlEvent(ServerEvent $event): void
    {
        if (defined('SIGINT')) {
            $loop = $event->getEventLoop();
            $server = $event->getServer();

            $loop->addSignal(
                SIGINT,
                function () use ($server, $loop): void {
                    if ($this->logger) {
                        $this->logger->notice('Stopping server ...');
                    }

                    foreach ($this->serverPushHandlerRegistry->getPushers() as $handler) {
                        $handler->close();

                        if ($this->logger) {
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

                    if ($this->logger) {
                        $this->logger->notice('Server stopped!');
                    }
                }
            );
        }
    }
}
