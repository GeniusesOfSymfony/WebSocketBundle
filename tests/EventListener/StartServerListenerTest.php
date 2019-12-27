<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerEvent;
use Gos\Bundle\WebSocketBundle\EventListener\StartServerListener;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
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
     * @var ServerPushHandlerRegistry
     */
    private $serverPushHandlerRegistry;

    /**
     * @var \Gos\Bundle\WebSocketBundle\EventListener\StartServerListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->periodicRegistry = new PeriodicRegistry();
        $this->serverPushHandlerRegistry = new ServerPushHandlerRegistry();

        $this->listener = new StartServerListener($this->periodicRegistry, $this->serverPushHandlerRegistry);
    }

    /**
     * @requires extension pcntl
     */
    public function testTheUserIsAuthenticatedWhenTheClientConnectEventIsDispatched(): void
    {
        $loop = $this->createMock(LoopInterface::class);
        $loop->expects($this->once())
            ->method('addSignal');

        $event = new ServerEvent($loop, $this->createMock(ServerInterface::class), false);

        $this->listener->bindPnctlEvent($event);
    }
}
