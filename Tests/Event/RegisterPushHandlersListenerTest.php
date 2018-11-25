<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Event;

use Gos\Bundle\WebSocketBundle\Event\RegisterPushHandlersListener;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\WampApplication;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;

class RegisterPushHandlersListenerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ServerPushHandlerRegistry
     */
    private $pushHandlerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WampApplication
     */
    private $wampApplication;

    /**
     * @var RegisterPushHandlersListener
     */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->pushHandlerRegistry = $this->createMock(ServerPushHandlerRegistry::class);
        $this->wampApplication = $this->createMock(WampApplication::class);

        $this->listener = new RegisterPushHandlersListener($this->pushHandlerRegistry, $this->wampApplication);
    }

    public function testThePushHandlersAreRegisteredToTheLoop()
    {
        $loop = $this->createMock(LoopInterface::class);

        $handler = $this->createMock(ServerPushHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($loop, $this->wampApplication);

        $this->pushHandlerRegistry->expects($this->once())
            ->method('getPushers')
            ->willReturn(['test' => $handler]);

        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('getEventLoop')
            ->willReturn($loop);

        $this->listener->registerPushHandlers($event);
    }
}
