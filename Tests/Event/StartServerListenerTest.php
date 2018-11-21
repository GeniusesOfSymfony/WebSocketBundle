<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Event;

use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Event\StartServerListener;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;

class StartServerListenerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PeriodicRegistry
     */
    private $periodicRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ServerPushHandlerRegistry
     */
    private $serverPushHandlerRegistry;

    /**
     * @var StartServerListener
     */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->periodicRegistry = $this->createMock(PeriodicRegistry::class);
        $this->serverPushHandlerRegistry = $this->createMock(ServerPushHandlerRegistry::class);

        $this->listener = new StartServerListener($this->periodicRegistry, $this->serverPushHandlerRegistry);
    }

    /**
     * @requires extension pcntl
     */
    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched()
    {
        $loop = $this->createMock(LoopInterface::class);
        $loop->expects($this->once())
            ->method('addSignal');

        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('getEventLoop')
            ->willReturn($loop);

        $event->expects($this->once())
            ->method('getServer')
            ->willReturn($this->createMock(ServerInterface::class));

        $this->listener->bindPnctlEvent($event);
    }
}
