<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Server\Type;

use Gos\Bundle\WebSocketBundle\Server\App\ServerBuilderInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\WebSocketServer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\MessageComponentInterface;
use React\EventLoop\LoopInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @var WebSocketServer
     */
    private $server;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serverBuilder = $this->createMock(ServerBuilderInterface::class);
        $this->loop = $this->createMock(LoopInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->server = new WebSocketServer(
            $this->serverBuilder,
            $this->loop,
            $this->eventDispatcher
        );
    }

    public function testTheServerIsLaunched()
    {
        $this->serverBuilder->expects($this->once())
            ->method('buildMessageStack')
            ->willReturn($this->createMock(MessageComponentInterface::class));

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch');

        $this->loop->expects($this->once())
            ->method('run');

        $this->server->launch('127.0.0.1', 1337, false);
    }
}
