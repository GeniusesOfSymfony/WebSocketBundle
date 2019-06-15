<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Event;

use Gos\Bundle\WebSocketBundle\Event\RegisterPeriodicTimersListener;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;

class RegisterPeriodicTimersListenerTest extends TestCase
{
    /**
     * @var PeriodicRegistry
     */
    private $periodicRegistry;

    /**
     * @var RegisterPeriodicTimersListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodicRegistry = new PeriodicRegistry();

        $this->listener = new RegisterPeriodicTimersListener($this->periodicRegistry);
    }

    public function testThePeriodicTimersAreRegisteredToTheLoop()
    {
        $handler = $this->createMock(PeriodicInterface::class);
        $handler->expects($this->once())
            ->method('getTimeout')
            ->willReturn(10);

        $this->periodicRegistry->addPeriodic($handler);

        $loop = $this->createMock(LoopInterface::class);
        $loop->expects($this->once())
            ->method('addPeriodicTimer');

        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('getEventLoop')
            ->willReturn($loop);

        $this->listener->registerPeriodics($event);
    }
}
