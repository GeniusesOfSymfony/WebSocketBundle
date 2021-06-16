<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Amqp;

use Gos\Bundle\PubSubRouterBundle\Router\Route;
use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerFailEvent;
use Gos\Bundle\WebSocketBundle\Event\PushHandlerSuccessEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactoryInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpServerPushHandler;
use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use Gos\Component\ReactAMQP\Consumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @requires extension amqp
 */
class AmqpServerPushHandlerTest extends TestCase
{
    /**
     * @var MockObject|RouterInterface
     */
    private $pubSubRouter;

    /**
     * @var WampRouter
     */
    private $router;

    /**
     * @var MockObject|SerializerInterface
     */
    private $serializer;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var MockObject|AmqpConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var TestLogger
     */
    private $logger;

    /**
     * @var AmqpServerPushHandler
     */
    private $pushHandler;

    public static function setUpBeforeClass(): void
    {
        if (!class_exists(Consumer::class)) {
            self::markTestSkipped('The "gos/react-amqp" package is not installed.');
        }
    }

    protected function setUp(): void
    {
        $this->pubSubRouter = $this->createMock(RouterInterface::class);
        $this->router = new WampRouter($this->pubSubRouter);

        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->connectionFactory = $this->createMock(AmqpConnectionFactoryInterface::class);

        $this->logger = new TestLogger();

        $this->pushHandler = new AmqpServerPushHandler($this->router, $this->serializer, $this->eventDispatcher, $this->connectionFactory);
        $this->pushHandler->setName('amqp');
        $this->pushHandler->setLogger($this->logger);
    }

    public function testAMessageIsHandledAndTheConnectionClosed(): void
    {
        $connection = $this->createMock(\AMQPConnection::class);
        $connection->expects(self::once())
            ->method('connect');

        $connection->expects(self::once())
            ->method('disconnect');

        $this->connectionFactory->expects(self::once())
            ->method('createConnection')
            ->willReturn($connection);

        $envelope = $this->createMock(\AMQPEnvelope::class);
        $envelope->expects(self::atLeastOnce())
            ->method('getBody')
            ->willReturn('["test data"]');

        $envelope->expects(self::atLeastOnce())
            ->method('getDeliveryTag')
            ->willReturn('delivered');

        // The consumer loops while there are messages in the queue, so a second loop is triggered and should return nothing to break it
        $queue = $this->createMock(\AMQPQueue::class);
        $queue->expects(self::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($envelope, null);

        $queue->expects(self::once())
            ->method('ack');

        $this->connectionFactory->expects(self::once())
            ->method('createQueue')
            ->willReturn($queue);

        $loop = $this->createMock(LoopInterface::class);
        $loop->expects(self::once())
            ->method('addPeriodicTimer')
            ->willReturn($this->createMock(TimerInterface::class));

        $app = $this->createMock(PushableWampServerInterface::class);
        $app->expects(self::once())
            ->method('onPush');

        $this->pushHandler->handle($loop, $app);

        // We need to pull the consumer out of the handler to trigger the internals
        $consumerProperty = (new \ReflectionClass($this->pushHandler))->getProperty('consumer');
        $consumerProperty->setAccessible(true);

        /** @var Consumer $consumer */
        $consumer = $consumerProperty->getValue($this->pushHandler);

        $this->serializer->expects(self::once())
            ->method('deserialize')
            ->willReturn(new Message('channel/42', ['test message']));

        $this->pubSubRouter->expects(self::once())
            ->method('match')
            ->willReturn(['test_channel', $this->createMock(Route::class), []]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PushHandlerSuccessEvent::class), GosWebSocketEvents::PUSHER_SUCCESS);

        $consumer();

        $this->pushHandler->close();
    }

    public function testAnErrorHandlingAMessageIsCaught(): void
    {
        $connection = $this->createMock(\AMQPConnection::class);
        $connection->expects(self::once())
            ->method('connect');

        $connection->expects(self::once())
            ->method('disconnect');

        $this->connectionFactory->expects(self::once())
            ->method('createConnection')
            ->willReturn($connection);

        $envelope = $this->createMock(\AMQPEnvelope::class);
        $envelope->expects(self::atLeastOnce())
            ->method('getBody')
            ->willReturn('["test data"]');

        $envelope->expects(self::atLeastOnce())
            ->method('getDeliveryTag')
            ->willReturn('delivered');

        // The consumer loops while there are messages in the queue, so a second loop is triggered and should return nothing to break it
        $queue = $this->createMock(\AMQPQueue::class);
        $queue->expects(self::exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($envelope, null);

        $queue->expects(self::once())
            ->method('reject');

        $this->connectionFactory->expects(self::once())
            ->method('createQueue')
            ->willReturn($queue);

        $loop = $this->createMock(LoopInterface::class);
        $loop->expects(self::once())
            ->method('addPeriodicTimer')
            ->willReturn($this->createMock(TimerInterface::class));

        $app = $this->createMock(PushableWampServerInterface::class);
        $app->expects(self::once())
            ->method('onPush')
            ->willThrowException(new \RuntimeException('Testing error handling'));

        $this->pushHandler->handle($loop, $app);

        // We need to pull the consumer out of the handler to trigger the internals
        $consumerProperty = (new \ReflectionClass($this->pushHandler))->getProperty('consumer');
        $consumerProperty->setAccessible(true);

        /** @var Consumer $consumer */
        $consumer = $consumerProperty->getValue($this->pushHandler);

        $this->serializer->expects(self::once())
            ->method('deserialize')
            ->willReturn(new Message('channel/42', ['test message']));

        $this->pubSubRouter->expects(self::once())
            ->method('match')
            ->willReturn(['test_channel', $this->createMock(Route::class), []]);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PushHandlerFailEvent::class), GosWebSocketEvents::PUSHER_FAIL);

        $consumer();

        $this->pushHandler->close();
    }
}
