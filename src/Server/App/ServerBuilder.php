<?php declare(strict_types=1);

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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ServerBuilder implements ServerBuilderInterface
{
    private LoopInterface $loop;
    private TopicManager $topicManager;
    private OriginRegistry $originRegistry;
    private EventDispatcherInterface $eventDispatcher;
    private bool $originCheck = false;
    private bool $keepalivePing = false;
    private int $keepaliveInterval = 30;
    private ?\SessionHandlerInterface $sessionHandler = null;

    public function __construct(
        LoopInterface $loop,
        TopicManager $topicManager,
        OriginRegistry $originRegistry,
        EventDispatcherInterface $eventDispatcher,
        bool $originCheck,
        bool $keepalivePing,
        int $keepaliveInterval
    ) {
        $this->loop = $loop;
        $this->topicManager = $topicManager;
        $this->originRegistry = $originRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->originCheck = $originCheck;
        $this->keepalivePing = $keepalivePing;
        $this->keepaliveInterval = $keepaliveInterval;
    }

    public function buildMessageStack(): MessageComponentInterface
    {
        $serverComponent = new WsServer(
            new WampConnectionPeriodicTimer(
                new WampServer($this->topicManager),
                $this->loop
            )
        );
        $serverComponent->setStrictSubProtocolCheck(false);

        if ($this->keepalivePing) {
            $serverComponent->enableKeepAlive($this->loop, $this->keepaliveInterval);
        }

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
