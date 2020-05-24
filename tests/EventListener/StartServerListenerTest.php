<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
use Gos\Bundle\WebSocketBundle\EventListener\StartServerListener;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\Socket\ServerInterface;

class StartServerListenerTest extends TestCase
{
    /**
     * @var PeriodicRegistry
     */
    private $periodicRegistry;

    /**
     * @var StartServerListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodicRegistry = new PeriodicRegistry();

        $this->listener = new StartServerListener($this->periodicRegistry);
    }

    /**
     * @requires extension pcntl
     */
    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched(): void
    {
        $loop = $this->createMock(LoopInterface::class);
        $loop->expects($this->once())
            ->method('addSignal');

        $event = new ServerLaunchedEvent(
            $loop,
            $this->createMock(ServerInterface::class),
            false
        );

        $this->listener->bindPnctlEvent($event);
    }
}
