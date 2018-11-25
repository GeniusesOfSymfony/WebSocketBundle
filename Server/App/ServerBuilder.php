<?php

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\OriginCheck;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\WampConnectionPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Server\WampServer;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Session\SessionProvider;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ServerBuilder
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var WampApplication
     */
    private $wampApplication;

    /**
     * @var TopicManager
     */
    private $topicManager;

    /**
     * @var OriginRegistry
     */
    private $originRegistry;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var bool
     */
    private $originCheck = false;

    /**
     * @var \SessionHandlerInterface|null
     */
    private $sessionHandler;

    public function __construct(
        LoopInterface $loop,
        WampApplication $wampApplication,
        TopicManager $topicManager,
        OriginRegistry $originRegistry,
        EventDispatcherInterface $eventDispatcher,
        bool $originCheck
    ) {
        $this->loop = $loop;
        $this->wampApplication = $wampApplication;
        $this->topicManager = $topicManager;
        $this->originRegistry = $originRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->originCheck = $originCheck;
    }

    public function buildMessageStack(): MessageComponentInterface
    {
        $serverComponent = new WsServer(
            new WampConnectionPeriodicTimer(
                new WampServer($this->wampApplication, $this->topicManager),
                $this->loop
            )
        );
        $serverComponent->setStrictSubProtocolCheck(false);

        if ($this->sessionHandler) {
            $serverComponent = new SessionProvider(
                $serverComponent,
                $this->sessionHandler
            );
        }

        if ($this->originCheck) {
            $allowedOrigins = array_merge(['localhost', '127.0.0.1'], $this->originRegistry->getOrigins());

            $serverComponent = new OriginCheck(
                $this->eventDispatcher,
                $serverComponent,
                $allowedOrigins
            );
        }

        return new HttpServer($serverComponent);
    }

    public function setSessionHandler(\SessionHandlerInterface $sessionHandler): void
    {
        $this->sessionHandler = $sessionHandler;
    }
}
