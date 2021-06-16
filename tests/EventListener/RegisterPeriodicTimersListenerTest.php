<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\EventListener\RegisterPeriodicTimersListener;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;

final class RegisterPeriodicTimersListenerTest extends TestCase
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

    public function testThePeriodicTimersAreRegisteredToTheLoop(): void
    {
        /** @var MockObject&PeriodicInterface $handler */
        $handler = $this->createMock(PeriodicInterface::class);
        $handler->expects(self::once())
            ->method('getTimeout')
            ->willReturn(10);

        $this->periodicRegistry->addPeriodic($handler);

        /** @var MockObject&LoopInterface $loop */
        $loop = $this->createMock(LoopInterface::class);
        $loop->expects(self::once())
            ->method('addPeriodicTimer');

        $event = new ServerLaunchedEvent(
            $loop,
            $this->createMock(ServerInterface::class),
            false
        );

        $listener = $this->listener;
        $listener($event);
    }
}
