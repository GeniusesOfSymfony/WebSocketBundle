<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\BlockedIpCheck;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\OriginCheck;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\WampConnectionPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Server\WampServer;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use Ratchet\Http\HttpServer;
use Ratchet\MessageComponentInterface;
use Ratchet\Session\IniOptionsHandler;
use Ratchet\Session\SessionProvider;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\LoopInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ServerBuilder implements ServerBuilderInterface
{
    private ?\SessionHandlerInterface $sessionHandler = null;

    /**
     * @param string[] $blockedIpAddresses
     */
    public function __construct(
        private LoopInterface $loop,
        private TopicManager $topicManager,
        private OriginRegistry $originRegistry,
        private EventDispatcherInterface $eventDispatcher,
        private bool $originCheck,
        private bool $keepalivePing,
        private int $keepaliveInterval,
        private bool $ipAddressCheck,
        private array $blockedIpAddresses,
    ) {
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
            // TODO - Remove extra args after https://github.com/ratchetphp/Ratchet/pull/859 is merged
            $serverComponent = new SessionProvider(
                $serverComponent,
                $this->sessionHandler,
                [],
                null,
                new IniOptionsHandler()
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

        $serverComponent = new HttpServer($serverComponent);

        if ($this->ipAddressCheck && !empty($this->blockedIpAddresses)) {
            $serverComponent = new BlockedIpCheck(
                $serverComponent,
                $this->eventDispatcher,
                $this->blockedIpAddresses
            );
        }

        return $serverComponent;
    }

    public function setSessionHandler(\SessionHandlerInterface $sessionHandler): void
    {
        $this->sessionHandler = $sessionHandler;
    }
}
