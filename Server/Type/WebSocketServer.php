<?php

namespace Gos\Bundle\WebSocketBundle\Server\Type;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilder;
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
     * @param ServerBuilder $serverBuilder
     * @param LoopInterface $loop
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ServerBuilder $serverBuilder,
        LoopInterface $loop,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->serverBuilder = $serverBuilder;
        $this->loop = $loop;
        $this->eventDispatcher = $eventDispatcher;
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

        $app = new IoServer(
            $this->serverBuilder->buildMessageStack(),
            $server,
            $this->loop
        );

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
