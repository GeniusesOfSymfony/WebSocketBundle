<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Wamp;

use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactoryInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampPusher;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;

class WampPusherTest extends TestCase
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
     * @var MockObject|WampConnectionFactoryInterface
     */
    private $connectionFactory;

    /**
     * @var WampPusher
     */
    private $pusher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pubSubRouter = $this->createMock(RouterInterface::class);
        $this->router = new WampRouter($this->pubSubRouter);

        $this->serializer = $this->createMock(SerializerInterface::class);

        $this->connectionFactory = $this->createMock(WampConnectionFactoryInterface::class);

        $this->pusher = new WampPusher($this->router, $this->serializer, $this->connectionFactory);
    }

    public function testAMessageIsPushedAndTheConnectionClosed(): void
    {
        $this->pubSubRouter->expects($this->once())
            ->method('generate')
            ->willReturn('channel/42');

        $connection = $this->createMock(ClientInterface::class);
        $connection->expects($this->once())
            ->method('connect');

        $connection->expects($this->once())
            ->method('disconnect');

        $this->connectionFactory->expects($this->once())
            ->method('createConnection')
            ->willReturn($connection);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->willReturn('{}');

        $this->pusher->push(['hello' => 'world'], 'test_channel');
        $this->pusher->close();
    }
}
