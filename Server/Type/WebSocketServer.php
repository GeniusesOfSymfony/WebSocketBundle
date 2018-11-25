<?php

namespace Gos\Bundle\WebSocketBundle\Server\Type;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicMemoryUsage;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilder;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use ProxyManager\Proxy\ProxyInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ratchet\Server\IoServer;
use React\EventLoop\LoopInterface;
use React\Socket\Server;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class WebSocketServer implements ServerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var ServerBuilder
     */
    protected $serverBuilder;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var PeriodicRegistry
     */
    protected $periodicRegistry;

    /**
     * @var WampApplication
     */
    protected $wampApplication;

    /**
     * @var ServerPushHandlerRegistry
     */
    protected $serverPusherHandlerRegistry;

    /**
     * @param ServerBuilder             $serverBuilder
     * @param LoopInterface             $loop
     * @param EventDispatcherInterface  $eventDispatcher
     * @param PeriodicRegistry          $periodicRegistry
     * @param WampApplication           $wampApplication
     * @param ServerPushHandlerRegistry $serverPushHandlerRegistry
     */
    public function __construct(
        ServerBuilder $serverBuilder,
        LoopInterface $loop,
        EventDispatcherInterface $eventDispatcher,
        PeriodicRegistry $periodicRegistry,
        WampApplication $wampApplication,
        ServerPushHandlerRegistry $serverPushHandlerRegistry
    ) {
        $this->serverBuilder = $serverBuilder;
        $this->loop = $loop;
        $this->eventDispatcher = $eventDispatcher;
        $this->periodicRegistry = $periodicRegistry;
        $this->wampApplication = $wampApplication;
        $this->serverPusherHandlerRegistry = $serverPushHandlerRegistry;
    }

    /**
     * @param bool $profile
     *
     * @throws \React\Socket\ConnectionException
     */
    public function launch($host, $port, $profile)
    {
        if ($this->logger) {
            $this->logger->info('Starting web socket');
        }

        $server = new Server("$host:$port", $this->loop);

        if (true === $profile) {
            $memoryUsagePeriodicTimer = new PeriodicMemoryUsage();

            if ($this->logger) {
                $memoryUsagePeriodicTimer->setLogger($this->logger);
            }

            $this->periodicRegistry->addPeriodic($memoryUsagePeriodicTimer);
        }

        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {
            $this->loop->addPeriodicTimer($periodic->getTimeout(), [$periodic, 'tick']);

            if ($this->logger) {
                $this->logger->info(
                    sprintf(
                        'Registered periodic callback %s, executed every %s seconds',
                        $periodic instanceof ProxyInterface ? get_parent_class($periodic) : get_class($periodic),
                        $periodic->getTimeout()
                    )
                );
            }
        }

        $app = new IoServer(
            $this->serverBuilder->buildMessageStack(),
            $server,
            $this->loop
        );

        // Push Transport Layer
        foreach ($this->serverPusherHandlerRegistry->getPushers() as $handler) {
            try {
                $handler->handle($this->loop, $this->wampApplication);
            } catch (\Exception $e) {
                if ($this->logger) {
                    $this->logger->error(
                        $e->getMessage(),
                        [
                            'exception' => $e,
                            'push_handler_name' => $handler->getName(),
                        ]
                    );
                }
            }
        }

        // Server Event Loop to add other services in the same loop.
        $event = new ServerEvent($this->loop, $server, $profile);
        $this->eventDispatcher->dispatch(Events::SERVER_LAUNCHED, $event);

        if ($this->logger) {
            $this->logger->info(
                sprintf(
                    'Launching %s on %s PID: %s',
                    $this->getName(),
                    $host.':'.$port,
                    getmypid()
                )
            );
        }

        $app->run();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Ratchet';
    }
}
