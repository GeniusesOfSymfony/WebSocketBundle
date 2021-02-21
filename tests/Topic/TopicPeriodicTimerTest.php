<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

final class TopicPeriodicTimerTest extends TestCase
{
    /**
     * @var MockObject&LoopInterface
     */
    private $loop;

    /**
     * @var TopicPeriodicTimer
     */
    private $topicPeriodicTimer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loop = $this->createMock(LoopInterface::class);

        $this->topicPeriodicTimer = new TopicPeriodicTimer($this->loop);
    }

    public function testRetrieveTheNamedPeriodicTimerWhenActive(): void
    {
        $callback = static function (): void {};
        $timeout = 10;

        /** @var MockObject&TopicInterface $topic */
        $topic = $this->createMock(TopicInterface::class);

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects($this->once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($topic, 'test', $timeout, $callback);

        $this->assertSame($timer, $this->topicPeriodicTimer->getAllPeriodicTimers($topic, 'test'));
    }

    public function testNoTimerIsReturnedWhenNotRegisteredAndActive(): void
    {
        /** @var MockObject&TopicInterface $topic */
        $topic = $this->createMock(TopicInterface::class);

        $this->assertFalse($this->topicPeriodicTimer->getAllPeriodicTimers($topic, 'test'));
    }

    public function testRetrieveThePeriodicTimersForATopic(): void
    {
        $callback = static function (): void {};
        $timeout = 10;

        /** @var MockObject&TopicInterface $topic */
        $topic = $this->createMock(TopicInterface::class);

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects($this->once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($topic, 'test', $timeout, $callback);

        $this->assertSame(['test' => $timer], $this->topicPeriodicTimer->getPeriodicTimers($topic));
    }

    public function testDetermineWhetherATopicHasBeenRegistered(): void
    {
        /** @var MockObject&TopicInterface $topic */
        $topic = $this->createMock(TopicInterface::class);

        $this->assertFalse($this->topicPeriodicTimer->isRegistered($topic));

        $callback = static function (): void {};
        $timeout = 10;

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects($this->once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($topic, 'test', $timeout, $callback);

        $this->assertTrue($this->topicPeriodicTimer->isRegistered($topic));
    }

    public function testCancelTheNamedPeriodicTimerWhenActive(): void
    {
        $callback = static function (): void {};
        $timeout = 10;

        /** @var MockObject&TopicInterface $topic */
        $topic = $this->createMock(TopicInterface::class);

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects($this->once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->loop->expects($this->once())
            ->method('cancelTimer')
            ->with($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($topic, 'test', $timeout, $callback);
        $this->topicPeriodicTimer->cancelPeriodicTimer($topic, 'test');
    }
}
