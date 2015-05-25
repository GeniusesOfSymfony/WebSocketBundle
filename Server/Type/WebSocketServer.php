<?php

namespace Gos\Bundle\WebSocketBundle\Server\Type;

use Gos\Bundle\WebSocketBundle\Event\Events;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use Gos\Component\PnctlEventLoopEmitter\PnctlEmitter;
use Gos\Component\RatchetStack\Builder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;
use React\Socket\Server;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NullSessionHandler;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class WebSocketServer implements ServerInterface
{
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
     * @var OriginRegistry|null
     */
    protected $originRegistry;

    /**
     * @var bool
     */
    protected $originCheck;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param string                   $host
     * @param int                      $port
     * @param EventDispatcherInterface $eventDispatcher
     * @param PeriodicRegistry         $periodicRegistry
     * @param WampApplication          $wampApplication
     * @param OriginRegistry           $originRegistry
     * @param bool                     $originCheck
     * @param LoggerInterface          $logger
     */
    public function __construct(
        $host,
        $port,
        EventDispatcherInterface $eventDispatcher,
        PeriodicRegistry $periodicRegistry,
        WampApplication $wampApplication,
        OriginRegistry $originRegistry,
        $originCheck,
        LoggerInterface $logger = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->eventDispatcher = $eventDispatcher;
        $this->periodicRegistry = $periodicRegistry;
        $this->wampApplication = $wampApplication;
        $this->originRegistry = $originRegistry;
        $this->originCheck = $originCheck;
        $this->logger = null === $logger ? new NullLogger() : $logger;
        $this->sessionHandler = new NullSessionHandler();
    }

    /**
     * @param \SessionHandlerInterface $sessionHandler
     */
    public function setSessionHandler(\SessionHandlerInterface $sessionHandler)
    {
        $this->sessionHandler = $sessionHandler;
    }

    public function launch()
    {
        $this->logger->info('Starting web socket');

        $stack = new Builder();

        /* @var $loop LoopInterface */
        $loop = Factory::create();

        $socket = new Server($loop);
        $socket->listen($this->port, $this->host);

        /** @var PeriodicInterface $periodic */
        foreach ($this->periodicRegistry->getPeriodics() as $periodic) {

            $loop->addPeriodicTimer($periodic->getTimeout(), [$periodic, 'tick']);

            $this->logger->info(sprintf(
                'Register periodic callback %s, executed each %s seconds',
                get_class($periodic),
                $periodic->getTimeout()
            ));
        }

        $allowedOrigins = array_merge(array('localhost', '127.0.0.1'), $this->originRegistry->getOrigins());

        $stack
            ->push('Ratchet\Server\IoServer', $socket, $loop)
            ->push('Ratchet\Http\HttpServer')
        ;

        if($this->originCheck){
            $stack->push('Gos\Bundle\WebSocketBundle\Server\App\Stack\OriginCheck', $allowedOrigins, $this->eventDispatcher);
        }

        $stack
            ->push('Ratchet\WebSocket\WsServer')
            ->push('Ratchet\Session\SessionProvider', $this->sessionHandler)
            ->push('Ratchet\Wamp\WampServer')
        ;

        $app = $stack->resolve($this->wampApplication);

        $pnctlEmitter = new PnctlEmitter($loop);

        $pnctlEmitter->on(SIGTERM, function() use ($socket, $loop) {

            $socket->emit('end');
            $socket->shutdown();
            $loop->stop();

            $this->logger->notice('Server stopped !');
        });

        $pnctlEmitter->on(SIGINT, function() use ($socket, $loop) {
            $this->logger->notice('Press CTLR+C again to stop the server');
            if(SIGINT === pcntl_sigtimedwait([SIGINT], $siginfo, 5)){
                $this->logger->notice('Stopping server ...');

                $socket->emit('end');
                $socket->shutdown();

                foreach($this->periodicRegistry->getPeriodics() as $periodic){
                    if($periodic instanceof TimerInterface && $loop->isTimerActive($periodic)){
                        $loop->cancelTimer($periodic);
                    }
                }

                $loop->stop();

                $this->logger->notice('Server stopped !');
            }else{
                $this->logger->notice('CTLR+C not pressed, continue to run normally');
            }
        });

        /* Server Event Loop to add other services in the same loop. */
        $event = new ServerEvent($loop);
        $this->eventDispatcher->dispatch(Events::SERVER_LAUNCHED, $event);

        $this->logger->info(sprintf(
            'Launching %s on %s PID: %s',
            $this->getName(),
            $this->getAddress(),
            getmypid()
        ));

        $app->run();
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->host . ':' . $this->port;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'Ratchet';
    }
}
