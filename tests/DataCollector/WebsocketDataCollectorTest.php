<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DataCollector;

use Gos\Bundle\WebSocketBundle\DataCollector\WebsocketDataCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * @group legacy
 */
class WebsocketDataCollectorTest extends TestCase
{
    public function testCollectNoPushers(): void
    {
        $collector = new WebsocketDataCollector();
        $collector->lateCollect();

        self::assertContainsOnly('int', $collector->getPusherCounts());
        self::assertSame(0, $collector->getPushTotal());
        self::assertSame('websocket', $collector->getName());
    }

    public function testCollectWithPushers(): void
    {
        $collector = new WebsocketDataCollector();

        $stopwatch = new Stopwatch();
        $eventName = 'push.websocket_test';

        $stopwatch->start($eventName, 'websocket');
        usleep(100);
        $stopwatch->stop($eventName);

        $collector->collectData($stopwatch->getEvent($eventName), 'websocket_test');

        $collector->lateCollect();

        self::assertContainsOnly('int', $collector->getPusherCounts());
        self::assertSame(1, $collector->getPushTotal());
        self::assertSame('websocket', $collector->getName());
    }
}
