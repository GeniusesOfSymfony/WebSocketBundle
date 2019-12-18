<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\EventListener\RegisterPushHandlersListener;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\PushableWampServerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;

class RegisterPushHandlersListenerTest extends TestCase
{
    /**
     * @var ServerPushHandlerRegistry
     */
    private $pushHandlerRegistry;

    /**
     * @var MockObject|PushableWampServerInterface
     */
    private $wampServer;

    /**
     * @var RegisterPushHandlersListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pushHandlerRegistry = new ServerPushHandlerRegistry();
        $this->wampServer = $this->createMock(PushableWampServerInterface::class);

        $this->listener = new RegisterPushHandlersListener($this->pushHandlerRegistry, $this->wampServer);
    }

    public function testThePushHandlersAreRegisteredToTheLoop(): void
    {
        $loop = $this->createMock(LoopInterface::class);

        $handler = $this->createMock(ServerPushHandlerInterface::class);
        $handler->expects($this->once())
            ->method('getName')
            ->willReturn('test');

        $handler->expects($this->once())
            ->method('handle')
            ->with($loop, $this->wampServer);

        $this->pushHandlerRegistry->addPushHandler($handler);

        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('getEventLoop')
            ->willReturn($loop);

        $this->listener->registerPushHandlers($event);
    }
}
