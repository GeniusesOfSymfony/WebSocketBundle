<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Dispatcher;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcher;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Gos\Bundle\WebSocketBundle\Topic\PushableTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Symfony\Component\HttpFoundation\ParameterBag;

final class TopicDispatcherTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TopicRegistry
     */
    private $topicRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WampRouter
     */
    private $wampRouter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TopicPeriodicTimer
     */
    private $topicPeriodicTimer;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TopicManager
     */
    private $topicManager;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var TopicDispatcher
     */
    private $dispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->topicRegistry      = $this->createMock(TopicRegistry::class);
        $this->wampRouter         = $this->createMock(WampRouter::class);
        $this->topicPeriodicTimer = $this->createMock(TopicPeriodicTimer::class);
        $this->topicManager       = $this->createMock(TopicManager::class);

        $this->logger = new TestLogger();

        $this->dispatcher = new TopicDispatcher($this->topicRegistry, $this->wampRouter, $this->topicPeriodicTimer, $this->topicManager);
        $this->dispatcher->setLogger($this->logger);
    }

    public function testAWebsocketSubscriptionIsDispatchedToItsHandler()
    {
        $handler = new class implements TopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                $this->called = true;
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onSubscribe($connection, $topic, $request);

        $this->assertTrue($handler->wasCalled());
    }

    public function testAWebsocketPushIsDispatchedToItsHandler()
    {
        $handler = new class implements TopicInterface, PushableTopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPush(Topic $topic, WampRequest $request, $data, $provider)
            {
                $this->called = true;
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicManager->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($this->createMock(Topic::class));

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $this->dispatcher->onPush($request, 'test', 'provider');

        $this->assertTrue($handler->wasCalled());
    }

    /**
     * @expectedException \Gos\Bundle\WebSocketBundle\Server\Exception\PushUnsupportedException
     * @expectedExceptionMessage The "topic.handler" topic does not support push notifications
     */
    public function testAWebsocketPushFailsIfTheHandlerDoesNotImplementTheRequiredInterface()
    {
        $handler = new class implements TopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicManager->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($this->createMock(Topic::class));

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $this->dispatcher->onPush($request, 'test', 'provider');
    }

    public function testAWebsocketUnsubscriptionIsDispatchedToItsHandler()
    {
        $handler = new class implements TopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                $this->called = true;
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onUnSubscribe($connection, $topic, $request);

        $this->assertTrue($handler->wasCalled());
    }

    public function testAWebsocketPublishIsDispatchedToItsHandler()
    {
        $handler = new class implements TopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                $this->called = true;
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        $this->assertTrue($handler->wasCalled());
    }

    public function testADispatchToASecuredTopicHandlerIsCompleted()
    {
        $handler = new class implements TopicInterface, SecuredTopicInterface
        {
            private $called = false;
            private $secured = false;

            public function secure(?ConnectionInterface $conn, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null, $provider = null)
            {
                $this->secured = true;
            }

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                $this->called = true;
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }

            public function wasSecured(): bool
            {
                return $this->secured;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        $this->assertTrue($handler->wasCalled());
        $this->assertTrue($handler->wasSecured());
    }

    public function testADispatchToAnUnregisteredPeriodicTopicTimerIsCompleted()
    {
        $handler = new class implements TopicInterface, TopicPeriodicTimerInterface
        {
            use TopicPeriodicTimerTrait;

            private $called = false;
            private $registered = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                $this->called = true;
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function registerPeriodicTimer(Topic $topic)
            {
                $this->registered = true;
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }

            public function wasRegistered(): bool
            {
                return $this->registered;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->topicPeriodicTimer->expects($this->once())
            ->method('isRegistered')
            ->with($handler)
            ->willReturn(false);

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        $this->assertTrue($handler->wasCalled());
        $this->assertTrue($handler->wasRegistered());
    }

    public function testPeriodicTimersAreClearedWhenAnEmptyTopicIsUnsubscribed()
    {
        $handler = new class implements TopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                $this->called = true;
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->topicPeriodicTimer->expects($this->once())
            ->method('clearPeriodicTimer')
            ->with($handler);

        $this->dispatcher->dispatch(TopicDispatcher::UNSUBSCRIPTION, $connection, $topic, $request);

        $this->assertTrue($handler->wasCalled());
    }

    public function testADispatchFailsWhenItsHandlerIsNotInTheRegistry()
    {
        $handler = new class implements TopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(false);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        $this->assertFalse($handler->wasCalled());

        $this->assertTrue($this->logger->hasErrorThatContains('Could not find topic dispatcher in registry for callback "topic.handler".'));
    }

    public function testTheConnectionIsClosedIfATopicCannotBeSecured()
    {
        $handler = new class implements TopicInterface, SecuredTopicInterface
        {
            private $called = false;
            private $secured = false;

            public function secure(ConnectionInterface $conn = null, Topic $topic, WampRequest $request, $payload = null, $exclude = null, $eligible = null, $provider = null)
            {
                throw new FirewallRejectionException('Access denied');
            }

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }

            public function wasSecured(): bool
            {
                return $this->secured;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $connection->expects($this->once())
            ->method('callError');

        $connection->expects($this->once())
            ->method('close');

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getId')
            ->willReturn('topic');

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        $this->assertFalse($handler->wasCalled());
        $this->assertFalse($handler->wasSecured());

        $this->assertTrue($this->logger->hasErrorThatContains('Access denied'));
    }

    public function testAnExceptionFromAHandlerIsCaughtAndProcessed()
    {
        $handler = new class implements TopicInterface
        {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request)
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible)
            {
                $this->called = true;

                throw new \Exception('Testing.');
            }

            public function getName()
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->expects($this->once())
            ->method('hasTopic')
            ->with('topic.handler')
            ->willReturn(true);

        $this->topicRegistry->expects($this->once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $connection->expects($this->once())
            ->method('callError');

        $topic = $this->createMock(Topic::class);
        $topic->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        $this->assertTrue($handler->wasCalled());

        $this->assertTrue($this->logger->hasErrorThatContains('Websocket error processing topic callback function.'));
    }
}
