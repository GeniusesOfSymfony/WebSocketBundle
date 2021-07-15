<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Event\ClientConnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientDisconnectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ClientErrorEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WampApplicationTest extends TestCase
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

        $this->application = new WampApplication(
            $this->rpcDispatcher,
            $this->topicDispatcher,
            $this->eventDispatcher,
            $this->clientStorage,
            $this->wampRouter
        );
    }

    public function testAMessageIsPublished(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $event = 'foo';
        $exclude = [];
        $eligible = [];

        $this->topicDispatcher->expects(self::once())
            ->method('onPublish');

        $this->application->onPublish($connection, $topic, $event, $exclude, $eligible);
    }

    /**
     * @group legacy
     */
    public function testAMessageIsPushed(): void
    {
        $request = new WampRequest(
            'channel_name',
            $this->createMock(Route::class),
            $this->createMock(ParameterBag::class),
            'channel/42'
        );

        $data = 'foo';
        $provider = 'test';

        $this->topicDispatcher->expects(self::once())
            ->method('onPush');

        $this->application->onPush($request, $data, $provider);
    }

    public function testARpcCallIsHandled(): void
    {
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $connection = $this->createMock(ConnectionInterface::class);
        $id = '42';
        $params = [];

        $this->rpcDispatcher->expects(self::once())
            ->method('dispatch');

        $this->application->onCall($connection, $id, $topic, $params);
    }

    public function testAClientSubscriptionIsHandled(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $this->topicDispatcher->expects(self::once())
            ->method('onSubscribe');

        $this->application->onSubscribe($connection, $topic);
    }

    public function testAClientUnsubscriptionIsHandled(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects(self::once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $this->topicDispatcher->expects(self::once())
            ->method('onUnSubscribe');

        $this->application->onUnSubscribe($connection, $topic);
    }

    public function testAConnectionIsOpened(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ClientConnectedEvent::class), GosWebSocketEvents::CLIENT_CONNECTED);

        $this->application->onOpen($connection);
    }

    public function testAConnectionIsClosed(): void
    {
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
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ClientErrorEvent::class), GosWebSocketEvents::CLIENT_ERROR);

        $this->application->onError($connection, new \Exception('Testing'));
    }
}
