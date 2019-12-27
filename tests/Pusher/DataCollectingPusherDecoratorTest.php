<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Pusher;

use Gos\Bundle\WebSocketBundle\DataCollector\WebsocketDataCollector;
use Gos\Bundle\WebSocketBundle\Pusher\DataCollectingPusherDecorator;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class DataCollectingPusherDecoratorTest extends TestCase
{
    /**
     * @var MockObject|PusherInterface
     */
    private $pusher;

    /**
     * @var MockObject|Stopwatch
     */
    private $stopwatch;

    /**
     * @var WebsocketDataCollector
     */
    private $collector;

    /**
     * @var DataCollectingPusherDecorator
     */
    private $decorator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pusher = $this->createMock(PusherInterface::class);
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->collector = new WebsocketDataCollector();

        $this->decorator = new DataCollectingPusherDecorator($this->pusher, $this->stopwatch, $this->collector);
    }

    public function testAPushIsProfiled(): void
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

        $this->decorator->push($data, $routeName, $routeParameters, $context);
    }

    public function testClosingThePusherIsPropagatedToTheDecoratedPusher(): void
    {
        $this->pusher->expects($this->once())
            ->method('close');

        $this->decorator->close();
    }

    public function testTheNameComesFromTheDecoratedPusher(): void
    {
        $name = 'test';

        $this->pusher->expects($this->once())
            ->method('getName')
            ->willReturn($name);

        $this->assertSame($name, $this->decorator->getName());
    }
}
