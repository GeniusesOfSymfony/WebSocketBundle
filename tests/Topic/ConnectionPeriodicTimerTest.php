<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Topic;

use Gos\Bundle\WebSocketBundle\Topic\ConnectionPeriodicTimer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class ConnectionPeriodicTimerTest extends TestCase
{
    /**
     * @var MockObject|ConnectionInterface
     */
    private $connection;

    /**
     * @var MockObject|LoopInterface
     */
    private $loop;

    /**
     * @var ConnectionPeriodicTimer
     */
    private $connectionPeriodicTimer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(ConnectionInterface::class);
        $this->connection->resourceId = 'abc123';
        $this->connection->WAMP = new \stdClass();
        $this->connection->WAMP->sessionId = '42a84b';

        $this->loop = $this->createMock(LoopInterface::class);

        $this->connectionPeriodicTimer = new ConnectionPeriodicTimer($this->connection, $this->loop);
    }

    public function testRetrieveTheNamedPeriodicTimerWhenActive(): void
    {
        $callback = static function (): void {};
        $timeout = 10;

        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects($this->once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->connectionPeriodicTimer->addPeriodicTimer('test', $timeout, $callback);

        $this->assertSame($timer, $this->connectionPeriodicTimer->getPeriodicTimer('test'));
    }

    public function testNoTimerIsReturnedWhenNotRegisteredAndActive(): void
    {
        $this->assertFalse($this->connectionPeriodicTimer->getPeriodicTimer('test'));
    }

    public function testCancelTheNamedPeriodicTimerWhenActive(): void
    {
        $callback = static function (): void {};
        $timeout = 10;

        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects($this->once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->loop->expects($this->once())
            ->method('cancelTimer')
            ->with($timer);

        $this->connectionPeriodicTimer->addPeriodicTimer('test', $timeout, $callback);
        $this->connectionPeriodicTimer->cancelPeriodicTimer('test');
    }

    public function testAnIteratorWithAllTimersIsReturned(): void
    {
        $this->assertInstanceOf(\ArrayIterator::class, $this->connectionPeriodicTimer->getIterator());
    }

    public function testTheTimerCanBeCounted(): void
    {
        $this->assertCount(0, $this->connectionPeriodicTimer);
    }
}
