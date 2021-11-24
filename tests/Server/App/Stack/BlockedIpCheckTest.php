<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Event\ConnectionRejectedEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Server\App\Stack\BlockedIpCheck;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class BlockedIpCheckTest extends TestCase
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
     * @var BlockedIpCheck
     */
    private $component;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->decoratedComponent = $this->createMock(MessageComponentInterface::class);

        $this->component = new BlockedIpCheck(
            $this->decoratedComponent,
            $this->eventDispatcher,
            ['192.168.1.1']
        );
    }

    public function testARequestPassesTheAddressCheck(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->remoteAddress = '127.0.0.1';

        $this->decoratedComponent->expects(self::once())
            ->method('onOpen')
            ->with($connection);

        $this->component->onOpen($connection);
    }

    /**
     * @group legacy
     */
    public function testARequestFailsTheAddressCheck(): void
    {
        /** @var MockObject&ConnectionInterface $connection */
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->remoteAddress = '192.168.1.1';

        $connection->expects(self::once())
            ->method('close');

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ConnectionRejectedEvent::class), GosWebSocketEvents::CONNECTION_REJECTED);

        $this->decoratedComponent->expects(self::never())
            ->method('onOpen');

        $this->component->onOpen($connection);
    }
}
