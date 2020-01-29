<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\EventListener;

use Gos\Bundle\WebSocketBundle\EventListener\KernelTerminateListener;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class KernelTerminateListenerTest extends TestCase
{
    /**
     * @var PusherRegistry
     */
    private $pusherRegistry;

    /**
     * @var KernelTerminateListener
     */
    private $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusherRegistry = new PusherRegistry();

        $this->listener = new KernelTerminateListener($this->pusherRegistry);
    }

    public function testPusherConnectionsAreClosedWhenTheKernelIsTerminatedForLegacyEvent(): void
    {
        if (!class_exists(PostResponseEvent::class)) {
            $this->markTestSkipped(sprintf('Test only applies to legacy "%s".', PostResponseEvent::class));
        }

        $pusher = $this->createMock(PusherInterface::class);
        $pusher->expects($this->once())
            ->method('getName')
            ->willReturn('Test');

        $pusher->expects($this->once())
            ->method('close');

        $this->pusherRegistry->addPusher($pusher);

        $event = new PostResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            $this->createMock(Request::class),
            $this->createMock(Response::class)
        );

        $this->listener->closeConnection($event);
    }

    public function testPusherConnectionsAreClosedWhenTheKernelIsTerminated(): void
    {
        if (!class_exists(TerminateEvent::class)) {
            $this->markTestSkipped(sprintf('Test only applies to "%s".', TerminateEvent::class));
        }

        $pusher = $this->createMock(PusherInterface::class);
        $pusher->expects($this->once())
            ->method('getName')
            ->willReturn('Test');

        $pusher->expects($this->once())
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
