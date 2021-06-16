<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Amqp;

use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactoryInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpPusher;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @requires extension amqp
 */
class AmqpPusherTest extends TestCase
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
     * @var MockObject|AmqpConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var AmqpPusher
     */
    private $pusher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pubSubRouter = $this->createMock(RouterInterface::class);
        $this->router = new WampRouter($this->pubSubRouter);

        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->connectionFactory = $this->createMock(AmqpConnectionFactoryInterface::class);

        $this->pusher = new AmqpPusher($this->router, $this->serializer, $this->connectionFactory);
    }

    public function testAMessageIsPushedAndTheConnectionClosed(): void
    {
        $this->pubSubRouter->expects(self::once())
            ->method('generate')
            ->willReturn('channel/42');

        $connection = $this->createMock(\AMQPConnection::class);
        $connection->expects(self::once())
            ->method('connect');

        $connection->expects(self::once())
            ->method('disconnect');

        $this->connectionFactory->expects(self::once())
            ->method('createConnection')
            ->willReturn($connection);

        $exchange = $this->createMock(\AMQPExchange::class);
        $exchange->expects(self::once())
            ->method('publish');

        $this->connectionFactory->expects(self::once())
            ->method('createExchange')
            ->willReturn($exchange);

        $this->serializer->expects(self::once())
            ->method('serialize')
            ->willReturn('{}');

        $this->pusher->push(['hello' => 'world'], 'test_channel');
        $this->pusher->close();
    }
}
