<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Dispatcher;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Dispatcher\TopicDispatcher;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\Exception\FirewallRejectionException;
use Gos\Bundle\WebSocketBundle\Topic\SecuredTopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
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
     * @var MockObject&TopicPeriodicTimer
     */
    private $topicPeriodicTimer;

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

        $this->dispatcher = new TopicDispatcher($this->topicRegistry, $this->wampRouter, $this->topicPeriodicTimer);
    }

    public function testAWebsocketSubscriptionIsDispatchedToItsHandler(): void
    {
        $handler = new class() implements TopicInterface {
            private bool $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                $this->called = true;
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onSubscribe($connection, $topic, $request);

        self::assertTrue($handler->wasCalled());
    }

    public function testAWebsocketUnsubscriptionIsDispatchedToItsHandler(): void
    {
        $handler = new class() implements TopicInterface {
            private bool $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                $this->called = true;
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onUnSubscribe($connection, $topic, $request);

        self::assertTrue($handler->wasCalled());
    }

    public function testAWebsocketUnsubscriptionIsDispatchedToItsHandlerAndPeriodicTimersAreClearedIfTheTopicNoLongerHasSubscribers(): void
    {
        $handler = new class() implements TopicInterface {
            private bool $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                $this->called = true;
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('count')
            ->willReturn(0);

        $this->topicPeriodicTimer->expects(self::once())
            ->method('clearPeriodicTimer')
            ->with($handler);

        $this->dispatcher->onUnSubscribe($connection, $topic, $request);

        self::assertTrue($handler->wasCalled());
    }

    public function testAWebsocketPublishIsDispatchedToItsHandler(): void
    {
        $handler = new class() implements TopicInterface {
            private bool $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
    }

    public function testAWebsocketPublishIsDispatchedToASecuredHandler(): void
    {
        $handler = new class() implements TopicInterface, SecuredTopicInterface {
            private bool $called = false;
            private bool $secured = false;

            public function secure(?ConnectionInterface $conn, Topic $topic, WampRequest $request, string | array | null $payload = null, ?array $exclude = null, ?array $eligible = null, ?string $provider = null): void
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

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
        self::assertTrue($handler->wasSecured());
    }

    public function testAWebsocketPublishIsDispatchedToAHandlerThatIsNotYetRegisteredAsAPeriodicTopicTimer(): void
    {
        $handler = new class() implements TopicInterface, TopicPeriodicTimerInterface {
            use TopicPeriodicTimerTrait;

            private bool $called = false;
            private bool $registered = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $this->topicPeriodicTimer->expects(self::once())
            ->method('isRegistered')
            ->with($handler)
            ->willReturn(false);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
        self::assertTrue($handler->wasRegistered());
    }

    public function testADispatchFailsWhenItsHandlerIsNotInTheRegistry(): void
    {
        $handler = new class() implements TopicInterface {
            private bool $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertFalse($handler->wasCalled());
    }

    public function testTheConnectionIsClosedIfATopicCannotBeSecured(): void
    {
        $handler = new class() implements TopicInterface, SecuredTopicInterface {
            private bool $called = false;
            private bool $secured = false;

            public function secure(?ConnectionInterface $conn, Topic $topic, WampRequest $request, string | array | null $payload = null, ?array $exclude = null, ?array $eligible = null, ?string $provider = null): void
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

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);
        $connection->expects(self::once())
            ->method('callError');

        $connection->expects(self::once())
            ->method('close');

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::once())
            ->method('getId')
            ->willReturn('topic');

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertFalse($handler->wasCalled());
        self::assertFalse($handler->wasSecured());
    }

    public function testAnExceptionFromAHandlerIsCaughtAndProcessed(): void
    {
        $handler = new class() implements TopicInterface {
            private bool $called = false;

            public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request): void
            {
                throw new \RuntimeException('Not expected to be called.');
            }

            public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, mixed $event, array $exclude, array $eligible): void
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

        /** @var MockObject&WampConnection $connection */
        $connection = $this->createMock(WampConnection::class);
        $connection->expects(self::once())
            ->method('callError');

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects(self::any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        self::assertTrue($handler->wasCalled());
    }
}
