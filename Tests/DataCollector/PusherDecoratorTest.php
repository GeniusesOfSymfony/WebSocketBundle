<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DataCollector;

use Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator;
use Gos\Bundle\WebSocketBundle\DataCollector\WebsocketDataCollector;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class PusherDecoratorTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PusherInterface
     */
    private $pusher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Stopwatch
     */
    private $stopwatch;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|WebsocketDataCollector
     */
    private $collector;

    /**
     * @var PusherDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusher = $this->createMock(PusherInterface::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->collector = $this->createMock(WebsocketDataCollector::class);

        $this->decorator = new PusherDecorator($this->pusher, $this->stopwatch, $this->collector);
    }

    public function testAPushIsProfiled()
    {
        $data = 'foo';
        $routeName = 'test.route';
        $routeParameters = [];
        $context = [];

        $this->pusher->expects($this->exactly(2))
            ->method('getName')
            ->willReturn('test');

        $this->stopwatch->expects($this->once())
            ->method('start')
            ->with('push.test', 'websocket');

        $this->pusher->expects($this->once())
            ->method('push')
            ->with($data, $routeName, $routeParameters, $context);

        $this->stopwatch->expects($this->once())
            ->method('stop')
            ->with('push.test');

        $stopwatchEvent = $this->createMock(StopwatchEvent::class);

        $this->stopwatch->expects($this->once())
            ->method('getEvent')
            ->with('push.test')
            ->willReturn($stopwatchEvent);

        $this->collector->expects($this->once())
            ->method('collectData')
            ->with($stopwatchEvent, 'test');

        $this->decorator->push($data, $routeName, $routeParameters, $context);
    }

    public function testClosingThePusherIsPropagatedToTheDecoratedPusher()
    {
        $this->pusher->expects($this->once())
            ->method('close');

        $this->decorator->close();
    }

    public function testTheNameComesFromTheDecoratedPusher()
    {
        $name = 'test';

        $this->pusher->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->assertSame($name, $this->decorator->getName());
    }
}
