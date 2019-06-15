<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Event;

use Gos\Bundle\WebSocketBundle\Event\RegisterPeriodicMemoryTimerListener;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;

class RegisterPeriodicMemoryTimerListenerTest extends TestCase
{
    /**
     * @var PeriodicRegistry
     */
    private $periodicRegistry;

    /**
     * @var RegisterPeriodicMemoryTimerListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodicRegistry = new PeriodicRegistry();

        $this->listener = new RegisterPeriodicMemoryTimerListener($this->periodicRegistry);
    }

    public function testThePeriodicMemoryTimerIsRegisteredWhenTheServerHasProfilingEnabled()
    {
        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('isProfiling')
            ->willReturn(true);

        $this->listener->registerPeriodicHandler($event);

        $this->assertNotEmpty($this->periodicRegistry->getPeriodics());
    }

    public function testThePeriodicMemoryTimerIsNotRegisteredWhenTheServerHasProfilingDisabled()
    {
        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('isProfiling')
            ->willReturn(false);

        $this->listener->registerPeriodicHandler($event);

        $this->assertEmpty($this->periodicRegistry->getPeriodics());
    }
}
