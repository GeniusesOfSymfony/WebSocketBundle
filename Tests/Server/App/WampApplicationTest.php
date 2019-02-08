<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App;

use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\RpcDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcherInterface;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class WampApplicationTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RpcDispatcherInterface
     */
    private $rpcDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TopicDispatcherInterface
     */
    private $topicDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClientStorageInterface
     */
    private $clientStorage;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WampRouter
     */
    private $wampRouter;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var WampApplication
     */
    private $application;

    protected function setUp()
    {
        parent::setUp();

        $this->rpcDispatcher = $this->createMock(RpcDispatcherInterface::class);
        $this->topicDispatcher = $this->createMock(TopicDispatcherInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->clientStorage = $this->createMock(ClientStorageInterface::class);
        $this->wampRouter = $this->createMock(WampRouter::class);

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

    public function testAMessageIsPublished()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->willReturn($token);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getId')
            ->willReturn('channel/42');

        $request = $this->createMock(WampRequest::class);

        $this->wampRouter->expects($this->once())
            ->method('match')
            ->with($topic)
            ->willReturn($request);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = new \stdClass();
        $connection->WAMP->clientStorageId = 'client';

        $event = 'foo';
        $exclude = [];
        $eligible = [];

        $this->topicDispatcher->expects($this->once())
            ->method('onPublish')
            ->with($connection, $topic, $request, $event, $exclude, $eligible);

        $this->application->onPublish($connection, $topic, $event, $exclude, $eligible);

        $this->assertTrue($this->logger->hasDebugThatContains('User user published to channel/42'));
    }

    public function testAMessageIsPushed()
    {
        $request = $this->createMock(WampRequest::class);
        $request->expects($this->once())
            ->method('getMatched')
            ->willReturn('channel/42');

        $data = 'foo';
        $provider = 'test';

        $this->topicDispatcher->expects($this->once())
            ->method('onPush')
            ->with($request, $data, $provider);

        $this->application->onPush($request, $data, $provider);

        $this->assertTrue($this->logger->hasInfoThatContains('Pusher test has pushed'));
    }

    public function testARpcCallIsHandled()
    {
        $topic = $this->createMock(Topic::class);

        $request = $this->createMock(WampRequest::class);

        $this->wampRouter->expects($this->once())
            ->method('match')
            ->with($topic)
            ->willReturn($request);

        $connection = $this->createMock(ConnectionInterface::class);
        $id = 42;
        $params = [];

        $this->rpcDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($connection, $id, $topic, $request, $params);

        $this->application->onCall($connection, $id, $topic, $params);
    }

    public function testAClientSubscriptionIsHandled()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->willReturn($token);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getId')
            ->willReturn('channel/42');

        $request = $this->createMock(WampRequest::class);

        $this->wampRouter->expects($this->once())
            ->method('match')
            ->with($topic)
            ->willReturn($request);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = new \stdClass();
        $connection->WAMP->clientStorageId = 'client';

        $event = 'foo';
        $exclude = [];
        $eligible = [];

        $this->topicDispatcher->expects($this->once())
            ->method('onSubscribe')
            ->with($connection, $topic, $request);

        $this->application->onSubscribe($connection, $topic);

        $this->assertTrue($this->logger->hasInfoThatContains('User user subscribed to channel/42'));
    }

    public function testAClientUnsubscriptionIsHandled()
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUsername')
            ->willReturn('user');

        $this->clientStorage->expects($this->once())
            ->method('getClient')
            ->willReturn($token);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getId')
            ->willReturn('channel/42');

        $request = $this->createMock(WampRequest::class);

        $this->wampRouter->expects($this->once())
            ->method('match')
            ->with($topic)
            ->willReturn($request);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = new \stdClass();
        $connection->WAMP->clientStorageId = 'client';

        $event = 'foo';
        $exclude = [];
        $eligible = [];

        $this->topicDispatcher->expects($this->once())
            ->method('onUnSubscribe')
            ->with($connection, $topic, $request);

        $this->application->onUnSubscribe($connection, $topic);

        $this->assertTrue($this->logger->hasInfoThatContains('User user unsubscribed from channel/42'));
    }

    public function testAConnectionIsOpened()
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->application->onOpen($connection);
    }

    public function testAConnectionIsClosed()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->WAMP = new \stdClass();
        $connection->WAMP->subscriptions = [];

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->application->onClose($connection);
    }

    public function testAnErrorIsHandled()
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->application->onError($connection, new \Exception('Testing'));
    }
}
