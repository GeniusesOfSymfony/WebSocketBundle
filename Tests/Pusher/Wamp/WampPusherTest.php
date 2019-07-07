<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher\Amqp;

use Gos\Bundle\PubSubRouterBundle\Router\RouterInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactoryInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampPusher;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Component\WebSocketClient\Wamp\Client;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
     * @var MessageSerializer
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

        $this->serializer = new MessageSerializer();

        $this->connectionFactory = $this->createMock(WampConnectionFactoryInterface::class);

        $this->pusher = new WampPusher($this->connectionFactory);
        $this->pusher->setRouter($this->router);
        $this->pusher->setSerializer($this->serializer);
    }

    public function testAMessageIsPushedAndTheConnectionClosed()
    {
        $this->pubSubRouter->expects($this->once())
            ->method('generate')
            ->willReturn('channel/42');

        $connection = $this->createMock(Client::class);
        $connection->expects($this->once())
            ->method('connect');

        $connection->expects($this->once())
            ->method('disconnect');

        $this->connectionFactory->expects($this->once())
            ->method('createConnection')
            ->willReturn($connection);

        $this->pusher->push(['hello' => 'world'], 'test_channel');
        $this->pusher->close();
    }
}
