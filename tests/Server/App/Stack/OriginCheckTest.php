<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Event\ClientRejectedEvent;
use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\OriginCheck;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OriginCheckTest extends TestCase
{
    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var MockObject|MessageComponentInterface
     */
    private $decoratedComponent;

    /**
     * @var OriginCheck
     */
    private $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->decoratedComponent = $this->createMock(MessageComponentInterface::class);

        $this->component = new OriginCheck(
            $this->eventDispatcher,
            $this->decoratedComponent
        );
        $this->component->allowedOrigins[] = 'localhost';
    }

    public function testARequestPassesTheOriginCheck(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $request = $this->createMock(RequestInterface::class);
        $request->expects(self::once())
            ->method('getHeaderLine')
            ->with('Origin')
            ->willReturn('localhost');

        $this->decoratedComponent->expects(self::once())
            ->method('onOpen')
            ->with($connection, $request);

        $this->component->onOpen($connection, $request);
    }

    public function testARequestFailsTheOriginCheck(): void
    {
        $this->component->allowedOrigins = ['socketo.me'];

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('send');

        $connection->expects(self::once())
            ->method('close');

        $request = $this->createMock(RequestInterface::class);
        $request->expects(self::once())
            ->method('getHeaderLine')
            ->with('Origin')
            ->willReturn('localhost');

        $this->eventDispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [self::isInstanceOf(ClientRejectedEvent::class), GosWebSocketEvents::CLIENT_REJECTED],
                [self::isInstanceOf(ConnectionRejectedEvent::class), GosWebSocketEvents::CONNECTION_REJECTED],
            );

        $this->decoratedComponent->expects(self::never())
            ->method('onOpen');

        $this->component->onOpen($connection, $request);
    }
}
