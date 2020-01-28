<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\EventListener\RegisterPeriodicMemoryTimerListener;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;

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

    public function testThePeriodicMemoryTimerIsRegisteredWhenTheServerHasProfilingEnabled(): void
    {
        $event = new ServerLaunchedEvent($this->createMock(LoopInterface::class), $this->createMock(ServerInterface::class), true);

        $this->listener->registerPeriodicHandler($event);

        $this->assertNotEmpty($this->periodicRegistry->getPeriodics());
    }

    public function testThePeriodicMemoryTimerIsNotRegisteredWhenTheServerHasProfilingDisabled(): void
    {
        $event = new ServerLaunchedEvent($this->createMock(LoopInterface::class), $this->createMock(ServerInterface::class), false);

        $this->listener->registerPeriodicHandler($event);

        $this->assertEmpty($this->periodicRegistry->getPeriodics());
    }
}
