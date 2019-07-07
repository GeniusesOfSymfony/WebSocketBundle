<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Server\App\Stack\OriginCheck;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    public function testARequestPassesTheOriginCheck()
    {
        $connection = $this->createMock(ConnectionInterface::class);

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Origin')
            ->willReturn('localhost');

        $this->decoratedComponent->expects($this->once())
            ->method('onOpen')
            ->with($connection, $request);

        $this->component->onOpen($connection, $request);
    }

    public function testARequestFailsTheOriginCheck()
    {
        $this->component->allowedOrigins = ['socketo.me'];

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('send');

        $connection->expects($this->once())
            ->method('close');

        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('getHeaderLine')
            ->with('Origin')
            ->willReturn('localhost');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->decoratedComponent->expects($this->never())
            ->method('onOpen');

        $this->component->onOpen($connection, $request);
    }
}
