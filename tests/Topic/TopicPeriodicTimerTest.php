<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Ratchet\Wamp\Topic;
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

        /** @var MockObject&TopicInterface $appTopic */
        $appTopic = $this->createMock(TopicInterface::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects(self::once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($appTopic, $topic, 'test', $timeout, $callback);

        self::assertSame($timer, $this->topicPeriodicTimer->getPeriodicTimer($appTopic, $topic, 'test'));
    }

    public function testNoTimerIsReturnedWhenNotRegisteredAndActive(): void
    {
        /** @var MockObject&TopicInterface $appTopic */
        $appTopic = $this->createMock(TopicInterface::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        self::assertFalse($this->topicPeriodicTimer->getPeriodicTimer($appTopic, $topic, 'test'));
    }

    public function testRetrieveThePeriodicTimersForATopic(): void
    {
        $callback = static function (): void {};
        $timeout = 10;

        /** @var MockObject&TopicInterface $appTopic */
        $appTopic = $this->createMock(TopicInterface::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects(self::once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($appTopic, $topic, 'test', $timeout, $callback);

        self::assertSame(['test' => $timer], $this->topicPeriodicTimer->getPeriodicTimers($appTopic, $topic));
    }

    public function testDetermineWhetherATopicHasBeenRegistered(): void
    {
        /** @var MockObject&TopicInterface $appTopic */
        $appTopic = $this->createMock(TopicInterface::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        self::assertFalse($this->topicPeriodicTimer->isRegistered($appTopic, $topic));

        $callback = static function (): void {};
        $timeout = 10;

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects(self::once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($appTopic, $topic, 'test', $timeout, $callback);

        self::assertTrue($this->topicPeriodicTimer->isRegistered($appTopic, $topic));
    }

    public function testCancelTheNamedPeriodicTimerWhenActive(): void
    {
        $callback = static function (): void {};
        $timeout = 10;

        /** @var MockObject&TopicInterface $appTopic */
        $appTopic = $this->createMock(TopicInterface::class);

        /** @var MockObject&Topic $topic */
        $topic = $this->createMock(Topic::class);

        /** @var MockObject&TimerInterface $timer */
        $timer = $this->createMock(TimerInterface::class);

        $this->loop->expects(self::once())
            ->method('addPeriodicTimer')
            ->with($timeout, $callback)
            ->willReturn($timer);

        $this->loop->expects(self::once())
            ->method('cancelTimer')
            ->with($timer);

        $this->topicPeriodicTimer->addPeriodicTimer($appTopic, $topic, 'test', $timeout, $callback);
        $this->topicPeriodicTimer->cancelPeriodicTimer($appTopic, $topic, 'test');
    }
}
