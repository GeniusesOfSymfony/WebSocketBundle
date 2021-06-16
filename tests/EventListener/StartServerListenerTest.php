<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\Event\ServerLaunchedEvent;
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
     * @var StartServerListener
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
        $loop->expects(self::exactly(2))
            ->method('addSignal')
            ->withConsecutive(
                [\SIGINT, self::isInstanceOf(\Closure::class)],
                [\SIGTERM, self::isInstanceOf(\Closure::class)]
            );

        $event = new ServerLaunchedEvent(
            $loop,
            $this->createMock(ServerInterface::class),
            false
        );

        $this->listener->bindPnctlEvent($event);
    }
}
