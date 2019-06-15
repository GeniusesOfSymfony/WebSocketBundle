<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DataCollector;

use Gos\Bundle\WebSocketBundle\DataCollector\WebsocketDataCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Stopwatch\Stopwatch;

class WebsocketDataCollectorTest extends TestCase
{
    public function testCollectNoPushers()
    {
        $collector = new WebsocketDataCollector();
        $collector->collect(new Request(), new Response());

        $this->assertContainsOnly('int', $collector->getPusherCounts());
        $this->assertSame(0, $collector->getPushTotal());
        $this->assertSame('websocket', $collector->getName());
    }

    public function testCollectWithPushers()
    {
        $collector = new WebsocketDataCollector();

        $stopwatch = new Stopwatch();
        $eventName = 'push.websocket_test';

        $stopwatch->start($eventName, 'websocket');
        usleep(100);
        $stopwatch->stop($eventName);

        $collector->collectData($stopwatch->getEvent($eventName), 'websocket_test');

        $collector->collect(new Request(), new Response());

        $this->assertContainsOnly('int', $collector->getPusherCounts());
        $this->assertSame(1, $collector->getPushTotal());
        $this->assertSame('websocket', $collector->getName());
    }
}
