<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Dispatcher;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcher;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Gos\Bundle\WebSocketBundle\Server\Exception\PushUnsupportedException;
use Gos\Bundle\WebSocketBundle\Topic\PushableTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicManager;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Symfony\Component\HttpFoundation\ParameterBag;

final class TopicDispatcherTest extends TestCase
{
    /**
     * @var TopicRegistry
     */
    private $topicRegistry;

    /**
     * @var WampRouter
     */
    private $wampRouter;

    /**
     * @var MockObject|TopicPeriodicTimer
     */
    private $topicPeriodicTimer;

    /**
     * @var MockObject|TopicManager
     */
    private $topicManager;

    /**
     * @var TopicDispatcher
     */
    private $dispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->topicRegistry = new TopicRegistry();
        $this->wampRouter = new WampRouter($this->createMock(RouterInterface::class));
        $this->topicPeriodicTimer = $this->createMock(TopicPeriodicTimer::class);
        $this->topicManager = $this->createMock(TopicManager::class);

        $this->dispatcher = new TopicDispatcher($this->topicRegistry, $this->wampRouter, $this->topicPeriodicTimer, $this->topicManager);
    }

    public function testAWebsocketSubscriptionIsDispatchedToItsHandler(): void
    {
        $handler = new class() implements TopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                $this->called = true;
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onSubscribe($connection, $topic, $request);

        self::assertTrue($handler->wasCalled());
    }

    /**
     * @group legacy
     */
    public function testAWebsocketPushIsDispatchedToItsHandler(): void
    {
        $handler = new class() implements TopicInterface, PushableTopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPush(Topic $topic, WampRequest $request, $data, string $provider): void
            {
                $this->called = true;
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicManager->expects(self::once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($this->createMock(Topic::class));

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $this->dispatcher->onPush($request, 'test', 'provider');

        self::assertTrue($handler->wasCalled());
    }

    /**
     * @group legacy
     */
    public function testAWebsocketPushFailsIfTheHandlerDoesNotImplementTheRequiredInterface(): void
    {
        $this->expectException(PushUnsupportedException::class);
        $this->expectExceptionMessage('The "topic.handler" topic does not support push notifications');

        $handler = new class() implements TopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicManager->expects(self::once())
            ->method('getTopic')
            ->with('topic.handler')
            ->willReturn($this->createMock(Topic::class));

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $this->dispatcher->onPush($request, 'test', 'provider');
    }

    public function testAWebsocketUnsubscriptionIsDispatchedToItsHandler(): void
    {
        $handler = new class() implements TopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                $this->called = true;
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onUnSubscribe($connection, $topic, $request);

        self::assertTrue($handler->wasCalled());
    }

    public function testAWebsocketPublishIsDispatchedToItsHandler(): void
    {
        $handler = new class() implements TopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                $this->called = true;
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
    }

    public function testADispatchToASecuredTopicHandlerIsCompleted(): void
    {
        $handler = new class() implements TopicInterface, SecuredTopicInterface {
            private $called = false;
            private $secured = false;

            public function secure(?ConnectionInterface $conn, Topic $topic, WampRequest $request, $payload = null, ?array $exclude = null, ?array $eligible = null, ?string $provider = null): void
            {
                $this->secured = true;
            }

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                $this->called = true;
            }

            public function getName(): string
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

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
        self::assertTrue($handler->wasSecured());
    }

    public function testADispatchToAnUnregisteredPeriodicTopicTimerIsCompleted(): void
    {
        $handler = new class() implements TopicInterface, TopicPeriodicTimerInterface {
            use TopicPeriodicTimerTrait;

            private $called = false;
            private $registered = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                $this->called = true;
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function registerPeriodicTimer(Topic $topic): void
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

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $this->topicPeriodicTimer->expects(self::once())
            ->method('isRegistered')
            ->with($handler)
            ->willReturn(false);

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
        self::assertTrue($handler->wasRegistered());
    }

    public function testPeriodicTimersAreClearedWhenAnEmptyTopicIsUnsubscribed(): void
    {
        $handler = new class() implements TopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                $this->called = true;
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->topicPeriodicTimer->expects(self::once())
            ->method('clearPeriodicTimer')
            ->with($handler);

        $this->dispatcher->dispatch(TopicDispatcher::UNSUBSCRIPTION, $connection, $topic, $request);

        self::assertTrue($handler->wasCalled());
    }

    public function testADispatchFailsWhenItsHandlerIsNotInTheRegistry(): void
    {
        $handler = new class() implements TopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertFalse($handler->wasCalled());
    }

    public function testTheConnectionIsClosedIfATopicCannotBeSecured(): void
    {
        $handler = new class() implements TopicInterface, SecuredTopicInterface {
            private $called = false;
            private $secured = false;

            public function secure(?ConnectionInterface $conn, Topic $topic, WampRequest $request, $payload = null, ?array $exclude = null, ?array $eligible = null, ?string $provider = null): void
            {
                throw new FirewallRejectionException('Access denied');
            }

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function getName(): string
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

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $connection->expects(self::once())
            ->method('callError');

        $connection->expects(self::once())
            ->method('close');

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getId')
            ->willReturn('topic');

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        self::assertFalse($handler->wasCalled());
        self::assertFalse($handler->wasSecured());
    }

    public function testAnExceptionFromAHandlerIsCaughtAndProcessed(): void
    {
        $handler = new class() implements TopicInterface {
            private $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible): void
            {
                $this->called = true;

                throw new \Exception('Testing.');
            }

            public function getName(): string
            {
                return 'topic.handler';
            }

            public function wasCalled(): bool
            {
                return $this->called;
            }
        };

        $this->topicRegistry->addTopic($handler);

        $route = new Route('hello/world', 'topic.handler');

        $request = new WampRequest('hello.world', $route, new ParameterBag(), 'topic.handler');

        $connection = $this->createMock(WampConnection::class);
        $connection->expects(self::once())
            ->method('callError');

        $topic = $this->createMock(Topic::class);
        $topic->expects(self::any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());

        $this->dispatcher->dispatch(TopicDispatcher::PUBLISH, $connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
    }
}
