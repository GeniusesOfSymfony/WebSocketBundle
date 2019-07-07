<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Zmq;

use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Pusher\Zmq\ZmqConnectionFactoryInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Zmq\ZmqPusher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @requires extension zmq
 */
class ZmqPusherTest extends TestCase
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
     * @var MockObject|ZmqConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var ZmqPusher
     */
    private $pusher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pubSubRouter = $this->createMock(RouterInterface::class);
        $this->router = new WampRouter($this->pubSubRouter);

        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->connectionFactory = $this->createMock(ZmqConnectionFactoryInterface::class);

        $this->pusher = new ZmqPusher($this->router, $this->serializer, $this->connectionFactory);
    }

    public function testAMessageIsPushedAndTheConnectionClosed()
    {
        $this->pubSubRouter->expects($this->once())
            ->method('generate')
            ->willReturn('channel/42');

        $connection = $this->createMock(\ZMQSocket::class);
        $connection->expects($this->once())
            ->method('connect');

        $connection->expects($this->once())
            ->method('disconnect');

        $this->connectionFactory->expects($this->once())
            ->method('createConnection')
            ->willReturn($connection);

        $this->connectionFactory->expects($this->exactly(2))
            ->method('buildConnectionDsn')
            ->willReturn('tcp://127.0.0.1:5555');

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{}');

        $this->pusher->push(['hello' => 'world'], 'test_channel');
        $this->pusher->close();
    }
}
