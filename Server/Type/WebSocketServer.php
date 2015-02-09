<?php

namespace Gos\Bundle\WebSocketBundle\Server\Type;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use Ratchet\Http\HttpServer;
use Ratchet\Http\HttpServerInterface;
use Ratchet\Server\IoServer;
use Ratchet\Session\SessionProvider;
use Ratchet\Wamp\WampServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use React\Socket\Server;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class WebSocketServer implements ServerInterface
{
    /**
     * @var HttpServerInterface
     */
    protected $app;

    /**
     * @var IoServer
     */
    protected $server;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @var Server
     */
    protected $socket;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var \SessionHandler|null
     */
    protected $sessionHandler;

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
     * @param string                   $host
     * @param int                      $port
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        $host,
        $port,
        EventDispatcherInterface $eventDispatcher,
        PeriodicRegistry $periodicRegistry,
        WampApplication $wampApplication
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->eventDispatcher = $eventDispatcher;
        $this->periodicRegistry = $periodicRegistry;
        $this->wampApplication = $wampApplication;
    }

    /**
     * @param \SessionHandlerInterface $sessionHandler
     */
    public function setSessionHandler(\SessionHandlerInterface $sessionHandler)
    {
        $this->sessionHandler = $sessionHandler;
    }

    /**
     * @return LoopInterface
     */
    public function getLoop()
    {
        return $this->loop;
    }

    public function launch()
    {
        if (null !== $this->sessionHandler) {
            $serverStack = new SessionProvider(
                new WampServer($this->wampApplication),
                $this->sessionHandler
            );
        } else {
            $serverStack = new WampServer(new WampServer($this->wampApplication));
        }

        $this->app = new HttpServer(new WsServer($serverStack));

        /** @var $loop LoopInterface */
        $this->loop = \React\EventLoop\Factory::create();

        $this->socket = new \React\Socket\Server($this->loop);

        $this->socket->listen($this->port, $this->host);

        /** @var PeriodicInterface $periodic */
        foreach ($this->periodicRegistry as $periodic) {
            $this->loop->addPeriodicTimer(($periodic->getTimeout()/1000), [$periodic, "tick"]);
        }

        $this->server = new \Ratchet\Server\IoServer($this->app, $this->socket, $this->loop);

        /* Server Event Loop to add other services in the same loop. */
        $event = new ServerEvent($this->loop);
        $this->eventDispatcher->dispatch(Events::SERVER_LAUNCHED, $event);

        $this->loop->run();
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->host . ":" . $this->port;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "Ratchet WS Server";
    }
}
