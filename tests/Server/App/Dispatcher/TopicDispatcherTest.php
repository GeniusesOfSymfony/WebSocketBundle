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
use Psr\Log\Test\TestLogger;
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
     * @var TestLogger
     */
    private $logger;

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

        $this->logger = new TestLogger();

        $this->dispatcher = new TopicDispatcher($this->topicRegistry, $this->wampRouter, $this->topicPeriodicTimer);
        $this->dispatcher->setLogger($this->logger);
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

        $this->assertTrue($handler->wasCalled());
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

        $this->assertTrue($handler->wasCalled());
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
        $topic->expects($this->once())
            ->method('count')
            ->willReturn(0);

        $this->topicPeriodicTimer->expects($this->once())
            ->method('clearPeriodicTimer')
            ->with($handler);

        $this->dispatcher->onUnSubscribe($connection, $topic, $request);

        $this->assertTrue($handler->wasCalled());
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

        $this->assertTrue($handler->wasCalled());
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

        $this->assertTrue($handler->wasCalled());
        $this->assertTrue($handler->wasSecured());
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
        $topic->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $this->topicPeriodicTimer->expects($this->once())
            ->method('isRegistered')
            ->with($handler)
            ->willReturn(false);

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        $this->assertTrue($handler->wasCalled());
        $this->assertTrue($handler->wasRegistered());
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

        $this->assertFalse($handler->wasCalled());

        $this->assertTrue($this->logger->hasErrorThatContains('Could not find topic dispatcher in registry for callback "topic.handler".'));
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
        $connection->expects($this->once())
            ->method('callError');

        $connection->expects($this->once())
            ->method('close');

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->once())
            ->method('getId')
            ->willReturn('topic');

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        $this->assertFalse($handler->wasCalled());
        $this->assertFalse($handler->wasSecured());

        $this->assertTrue($this->logger->hasErrorThatContains('Access denied'));
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
        $connection->expects($this->once())
            ->method('callError');

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);
        $topic->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());

        $this->dispatcher->onPublish($connection, $topic, $request, 'test', [], []);

        $this->assertTrue($handler->wasCalled());

        $this->assertTrue($this->logger->hasErrorThatContains('Websocket error processing topic callback function.'));
    }
}
