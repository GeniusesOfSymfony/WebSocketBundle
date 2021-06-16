<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\EventListener\ClosePusherConnectionsListener;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ClosePusherConnectionsListenerTest extends TestCase
{
    /**
     * @var PusherRegistry
     */
    private $pusherRegistry;

    /**
     * @var ClosePusherConnectionsListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusherRegistry = new PusherRegistry();

        $this->listener = new ClosePusherConnectionsListener($this->pusherRegistry);
    }

    public function testPusherConnectionsAreClosedWhenTheKernelIsTerminated(): void
    {
        $pusher = $this->createMock(PusherInterface::class);
        $pusher->expects(self::once())
            ->method('getName')
            ->willReturn('Test');

        $pusher->expects(self::once())
            ->method('close');

        $this->pusherRegistry->addPusher($pusher);

        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            $this->createMock(Response::class)
        );

        $this->listener->closeConnection($event);
    }
}
