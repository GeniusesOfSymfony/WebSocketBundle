<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Gos\Component\PnctlEventLoopEmitter\PnctlEmitter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
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
        $server->shutdown();

        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
            if ($periodic instanceof TimerInterface && $loop->isTimerActive($periodic)) {
                $loop->cancelTimer($periodic);
            }
        }

        $loop->stop();

        $this->logger->notice('Server stopped !');
    }

    /**
     * @param ServerEvent $event
     */
    public function bindPnctlEvent(ServerEvent $event)
    {
        if (!extension_loaded('pcntl')) {
            return;
        }

        $loop = $event->getEventLoop();
        $server = $event->getServer();

        $pnctlEmitter = new PnctlEmitter($loop);

        $pnctlEmitter->on(SIGTERM, function () use ($server, $loop) {
            $this->closure($server, $loop);
        });

        $pnctlEmitter->on(SIGINT, function () use ($pnctlEmitter) {

            $this->logger->notice('Press CTLR+C again to stop the server');

            if (SIGINT === pcntl_sigtimedwait([SIGINT], $siginfo, 5)) {
                $pnctlEmitter->emit(SIGTERM);
            } else {
                $this->logger->notice('CTLR+C not pressed, continue to run normally');
            }
        });
    }
}
