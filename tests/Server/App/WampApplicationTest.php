<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WampApplicationTest extends TestCase
{
    /**
     * @var MockObject|RpcDispatcherInterface
     */
    private $rpcDispatcher;

    /**
     * @var MockObject|TopicDispatcherInterface
     */
    private $topicDispatcher;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var MockObject|ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var WampRouter
     */
    private $wampRouter;

    /**
     * @var MockObject|RouterInterface
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
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $this->clientStorage->expects($this->exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->willReturn(true);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->willReturn($token);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects($this->once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $event = 'foo';
        $exclude = [];
        $eligible = [];

        $this->topicDispatcher->expects($this->once())
            ->method('onPublish');

        $this->application->onPublish($connection, $topic, $event, $exclude, $eligible);

        $this->assertTrue($this->logger->hasDebugThatContains('User user published to channel/42'));
    }

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

        $this->topicDispatcher->expects($this->once())
            ->method('onPush');

        $this->application->onPush($request, $data, $provider);

        $this->assertTrue($this->logger->hasInfoThatContains('Pusher test has pushed'));
    }

    public function testARpcCallIsHandled(): void
    {
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects($this->once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $connection = $this->createMock(ConnectionInterface::class);
        $id = '42';
        $params = [];

        $this->rpcDispatcher->expects($this->once())
            ->method('dispatch');

        $this->application->onCall($connection, $id, $topic, $params);
    }

    public function testAClientSubscriptionIsHandled(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $this->clientStorage->expects($this->exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->willReturn(true);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->willReturn($token);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects($this->once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $this->topicDispatcher->expects($this->once())
            ->method('onSubscribe');

        $this->application->onSubscribe($connection, $topic);

        $this->assertTrue($this->logger->hasInfoThatContains('User user subscribed to channel/42'));
    }

    public function testAClientUnsubscriptionIsHandled(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->resourceId = 'resource';

        $this->clientStorage->expects($this->exactly(2))
            ->method('getStorageId')
            ->with($connection)
            ->willReturn($connection->resourceId);

        $this->clientStorage->expects($this->once())
            ->method('hasClient')
            ->willReturn(true);

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->willReturn($token);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn('channel/42');

        $this->decoratedRouter->expects($this->once())
            ->method('match')
            ->with('channel/42')
            ->willReturn(['channel_name', $this->createMock(Route::class), []]);

        $this->topicDispatcher->expects($this->once())
            ->method('onUnSubscribe');

        $this->application->onUnSubscribe($connection, $topic);

        $this->assertTrue($this->logger->hasInfoThatContains('User user unsubscribed from channel/42'));
    }

    public function testAConnectionIsOpened(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->application->onOpen($connection);
    }

    public function testAConnectionIsClosed(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = new \stdClass();
        $connection->WAMP->subscriptions = [];

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->application->onClose($connection);
    }

    public function testAnErrorIsHandled(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->application->onError($connection, new \Exception('Testing'));
    }
}
