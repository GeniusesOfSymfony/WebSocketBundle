<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\Type;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\GosWebSocketEvents;
use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilderInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\WebSocketServer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class WebSocketServerTest extends TestCase
{
    /**
     * @var MockObject|ServerBuilderInterface
     */
    private $serverBuilder;

    /**
     * @var MockObject|LoopInterface
     */
    private $loop;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBuilder = $this->createMock(ServerBuilderInterface::class);
        $this->loop = $this->createMock(LoopInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    /**
     * @runInSeparateProcess
     */
    public function testTheServerIsLaunched(): void
    {
        $this->serverBuilder->expects(self::once())
            ->method('buildMessageStack')
            ->willReturn($this->createMock(MessageComponentInterface::class));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ServerLaunchedEvent::class), GosWebSocketEvents::SERVER_LAUNCHED);

        $this->loop->expects(self::once())
            ->method('run');

        (new WebSocketServer($this->serverBuilder, $this->loop, $this->eventDispatcher))
            ->launch('127.0.0.1', 1337, false);
    }

    /**
     * @runInSeparateProcess
     */
    public function testTheServerIsLaunchedWithTlsSupport(): void
    {
        $this->serverBuilder->expects(self::once())
            ->method('buildMessageStack')
            ->willReturn($this->createMock(MessageComponentInterface::class));

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ServerLaunchedEvent::class), GosWebSocketEvents::SERVER_LAUNCHED);

        $this->loop->expects(self::once())
            ->method('run');

        (new WebSocketServer($this->serverBuilder, $this->loop, $this->eventDispatcher, true, ['verify_peer' => false]))
            ->launch('127.0.0.1', 1337, false);
    }
}
