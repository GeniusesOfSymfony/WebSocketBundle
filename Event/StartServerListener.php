<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Socket\Server;

class StartServerListener
{
    /**
     * @var PeriodicRegistry
     */
    protected $periodicRegistry;

    /**
     * @var ServerPushHandlerRegistry
     */
    protected $serverPushHandlerRegistry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param PeriodicRegistry          $periodicRegistry
     * @param ServerPushHandlerRegistry $serverPushHandlerRegistry
     * @param LoggerInterface|null      $logger
     */
    public function __construct(
        PeriodicRegistry $periodicRegistry,
        ServerPushHandlerRegistry $serverPushHandlerRegistry,
        LoggerInterface $logger = null
    ) {
        $this->periodicRegistry = $periodicRegistry;
        $this->serverPushHandlerRegistry = $serverPushHandlerRegistry;
        $this->logger = null === $logger ? new NullLogger() : $logger;
    }

    /**
     * @param Server        $server
     * @param LoopInterface $loop
     */
    protected function closure(Server $server, LoopInterface $loop)
    {
        $this->logger->notice('Stopping server ...');

        foreach ($this->serverPushHandlerRegistry->getPushers() as $handler) {
            $handler->close();
            $this->logger->info(sprintf('Stop %s push handler', $handler->getName()));
        }

        $server->emit('end');
        $server->close();

        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
            if ($periodic instanceof TimerInterface) {
                $loop->cancelTimer($periodic);
            }
        }

        $loop->stop();

        $this->logger->notice('Server stopped!');
    }

    /**
     * @param ServerEvent $event
     */
    public function bindPnctlEvent(ServerEvent $event)
    {
        $loop = $event->getEventLoop();
        $server = $event->getServer();

        if (defined('SIGINT')) {
            $loop->addSignal(SIGINT, function () use ($server, $loop) {
                $this->closure($server, $loop);
            });
        }
    }
}
