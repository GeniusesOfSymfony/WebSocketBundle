<?php

namespace Gos\Bundle\WebSocketBundle\Tests\Event;

use Gos\Bundle\WebSocketBundle\Event\RegisterPeriodicMemoryTimerListener;
use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;

class RegisterPeriodicMemoryTimerListenerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PeriodicRegistry
     */
    private $periodicRegistry;

    /**
     * @var RegisterPeriodicMemoryTimerListener
     */
    private $listener;

    protected function setUp()
    {
        parent::setUp();

        $this->periodicRegistry = $this->createMock(PeriodicRegistry::class);

        $this->listener = new RegisterPeriodicMemoryTimerListener($this->periodicRegistry);
    }

    public function testThePeriodicMemoryTimerIsRegisteredWhenTheServerHasProfilingEnabled()
    {
        $this->periodicRegistry->expects($this->once())
            ->method('addPeriodic');

        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('isProfiling')
            ->willReturn(true);

        $this->listener->registerPeriodicHandler($event);
    }

    public function testThePeriodicMemoryTimerIsNotRegisteredWhenTheServerHasProfilingDisabled()
    {
        $this->periodicRegistry->expects($this->never())
            ->method('addPeriodic');

        $event = $this->createMock(ServerEvent::class);
        $event->expects($this->once())
            ->method('isProfiling')
            ->willReturn(false);

        $this->listener->registerPeriodicHandler($event);
    }
}
