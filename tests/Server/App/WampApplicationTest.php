<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class WampApplicationTest extends TestCase
{
    /**
     * @var MockObject&RpcDispatcherInterface
     */
    private $rpcDispatcher;

    /**
     * @var MockObject&TopicDispatcherInterface
     */
    private $topicDispatcher;

    /**
     * @var MockObject&EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var MockObject&ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var WampRouter
     */
    private $wampRouter;

    /**
     * @var MockObject&RouterInterface
     */
    private $decoratedRouter;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var WampApplication
     */
    private $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decoratedRouter = $this->createMock(RouterInterface::class);
        $this->rpcDispatcher = $this->createMock(RpcDispatcherInterface::class);
        $this->topicDispatcher = $this->createMock(TopicDispatcherInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->wampRouter = new WampRouter($this->decoratedRouter);

        $this->logger = new TestLogger();

        $this->application = new WampApplication(
            $this->rpcDispatcher,
            $this->topicDispatcher,
            $this->eventDispatcher,
            $this->clientStorage,
            $this->wampRouter
        );
        $this->application->setLogger($this->logger);
    }

    public function testAMessageIsPublished(): void
    {
        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('user');

        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $this->clientStorage->expects(self::exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->willReturn(true);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->willReturn($token);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', new Route('channel/{id}', 'strlen', [], ['id' => '\d+']), []]);

        $event = 'foo';
        $exclude = [];
        $eligible = [];

        $this->topicDispatcher->expects(self::once())
            ->method('onPublish');

        $this->application->onPublish($connection, $topic, $event, $exclude, $eligible);

        self::assertTrue($this->logger->hasDebugThatContains('User user published to channel/42'));
    }

    public function testARpcCallIsHandled(): void
    {
        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', new Route('channel/{id}', 'strlen', [], ['id' => '\d+']), []]);

        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $id = '42';
        $params = [];

        $this->rpcDispatcher->expects(self::once())
            ->method('dispatch');

        $this->application->onCall($connection, $id, $topic, $params);
    }

    public function testAClientSubscriptionIsHandled(): void
    {
        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('user');

        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $this->clientStorage->expects(self::exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->willReturn(true);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->willReturn($token);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', new Route('channel/{id}', 'strlen', [], ['id' => '\d+']), []]);

        $this->topicDispatcher->expects(self::once())
            ->method('onSubscribe');

        $this->application->onSubscribe($connection, $topic);

        self::assertTrue($this->logger->hasInfoThatContains('User user subscribed to channel/42'));
    }

    public function testAClientUnsubscriptionIsHandled(): void
    {
        /** @var MockObject&AbstractToken $token */
        $token = $this->createMock(AbstractToken::class);
        $token->expects(self::once())
            ->method(method_exists(AbstractToken::class, 'getUserIdentifier') ? 'getUserIdentifier' : 'getUsername')
            ->willReturn('user');

        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $this->clientStorage->expects(self::exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects(self::once())
            ->method('hasClient')
            ->willReturn(true);

        $this->clientStorage->expects(self::once())
            ->method('getClient')
            ->willReturn($token);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', new Route('channel/{id}', 'strlen', [], ['id' => '\d+']), []]);

        $this->topicDispatcher->expects(self::once())
            ->method('onUnSubscribe');

        $this->application->onUnSubscribe($connection, $topic);

        self::assertTrue($this->logger->hasInfoThatContains('User user unsubscribed from channel/42'));
    }

    public function testAConnectionIsOpened(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ClientConnectedEvent::class), GosWebSocketEvents::CLIENT_CONNECTED);

        $this->application->onOpen($connection);
    }

    public function testAConnectionIsClosed(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = new \stdClass();
        $connection->WAMP->subscriptions = [];

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ClientDisconnectedEvent::class), GosWebSocketEvents::CLIENT_DISCONNECTED);

        $this->application->onClose($connection);
    }

    /**
     * @group legacy
     */
    public function testAnErrorIsHandled(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ClientErrorEvent::class), GosWebSocketEvents::CLIENT_ERROR);

        $this->application->onError($connection, new \Exception('Testing'));
    }
}
