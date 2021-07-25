<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use ReturnTypeWillChange;

/**
 * @implements \IteratorAggregate<string, array<string, TimerInterface>>
 */
class TopicPeriodicTimer implements \IteratorAggregate
{
    /**
     * @var array<string, array<string, TimerInterface>>
     */
    protected array $registry = [];
    protected LoopInterface $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    /**
     * @return TimerInterface|bool
     */
    public function getAllPeriodicTimers(TopicInterface $topic, string $name)
    {
        if (!$this->isPeriodicTimerActive($topic, $name)) {
            return false;
        }

        $namespace = $this->getTopicNamespace($topic);

        return $this->registry[$namespace][$name];
    }

    /**
     * @return TimerInterface[]
     */
    public function getPeriodicTimers(TopicInterface $topic): array
    {
        $namespace = $this->getTopicNamespace($topic);

        return $this->registry[$namespace] ?? [];
    }

    /**
     * @param int|float $timeout
     */
    public function addPeriodicTimer(TopicInterface $topic, string $name, $timeout, callable $callback): void
    {
        $namespace = $this->getTopicNamespace($topic);

        if (!isset($this->registry[$namespace])) {
            $this->registry[$namespace] = [];
        }

        $this->registry[$namespace][$name] = $this->loop->addPeriodicTimer($timeout, $callback);
    }

    public function isRegistered(TopicInterface $topic): bool
    {
        $namespace = $this->getTopicNamespace($topic);

        return isset($this->registry[$namespace]);
    }

    public function isPeriodicTimerActive(TopicInterface $topic, string $name): bool
    {
        $namespace = $this->getTopicNamespace($topic);

        return isset($this->registry[$namespace][$name]);
    }

    public function cancelPeriodicTimer(TopicInterface $topic, string $name): void
    {
        $namespace = $this->getTopicNamespace($topic);

        if (!isset($this->registry[$namespace][$name])) {
            return;
        }

        $timer = $this->registry[$namespace][$name];
        $this->loop->cancelTimer($timer);
        unset($this->registry[$namespace][$name]);
    }

    public function clearPeriodicTimer(TopicInterface $topic): void
    {
        $namespace = $this->getTopicNamespace($topic);
        unset($this->registry[$namespace]);
    }

    /**
     * @return \ArrayIterator<string, array<string, TimerInterface>>
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return new \ArrayIterator($this->registry);
    }

    private function getTopicNamespace(TopicInterface $topic): string
    {
        return spl_object_hash($topic);
    }
}
