<?php

namespace Gos\Bundle\WebSocketBundle\Event;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Gos\Component\PnctlEventLoopEmitter\PnctlEmitter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\Timer\TimerInterface;

class StartServerListener
{
    /**
     * @var PeriodicRegistry
     */
    protected $periodicRegistry;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param PeriodicRegistry $periodicRegistry
     * @param LoggerInterface  $logger
     */
    public function __construct(PeriodicRegistry $periodicRegistry, LoggerInterface $logger = null)
    {
        $this->periodicRegistry = $periodicRegistry;
        $this->logger = null === $logger ? new NullLogger() : $logger;
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

            $server->emit('end');
            $server->shutdown();
            $loop->stop();

            $this->logger->notice('Server stopped !');
        });

        $pnctlEmitter->on(SIGINT, function () use ($server, $loop) {

            $this->logger->notice('Press CTLR+C again to stop the server');

            if (SIGINT === pcntl_sigtimedwait([SIGINT], $siginfo, 5)) {
                $this->logger->notice('Stopping server ...');

                $server->emit('end');
                $server->shutdown();

                foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
                    if ($periodic instanceof TimerInterface && $loop->isTimerActive($periodic)) {
                        $loop->cancelTimer($periodic);
                    }
                }

                $loop->stop();

                $this->logger->notice('Server stopped !');
            } else {
                $this->logger->notice('CTLR+C not pressed, continue to run normally');
            }
        });
    }
}
