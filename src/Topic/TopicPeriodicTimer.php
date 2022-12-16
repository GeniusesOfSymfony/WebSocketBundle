<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\Wamp\Topic;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/**
 * @implements \IteratorAggregate<string, array<string, TimerInterface>>
 */
class TopicPeriodicTimer implements \IteratorAggregate
{
    /**
     * @var array<string, array<string, array<string, TimerInterface>>>
     */
    protected array $registry = [];
    protected LoopInterface $loop;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function getPeriodicTimer(TopicInterface $appTopic, Topic $topic, string $name): TimerInterface|bool
    {
        if (!$this->isPeriodicTimerActive($appTopic, $topic, $name)) {
            return false;
        }

        $namespace = $this->getTopicNamespace($appTopic);

        return $this->registry[$namespace][$topic->getId()][$name];
    }

    /**
     * @return array<string, TimerInterface>
     */
    public function getPeriodicTimers(TopicInterface $appTopic, Topic $topic): array
    {
        $namespace = $this->getTopicNamespace($appTopic);

        return $this->registry[$namespace][$topic->getId()] ?? [];
    }

    public function addPeriodicTimer(TopicInterface $appTopic, Topic $topic, string $name, int|float $timeout, callable $callback): void
    {
        $namespace = $this->getTopicNamespace($appTopic);

        if (!isset($this->registry[$namespace])) {
            $this->registry[$namespace] = [];
        }

        if (!isset($this->registry[$namespace][$topic->getId()])) {
            $this->registry[$namespace][$topic->getId()] = [];
        }

        $this->registry[$namespace][$topic->getId()][$name] = $this->loop->addPeriodicTimer($timeout, $callback);
    }

    public function isRegistered(TopicInterface $appTopic, Topic $topic): bool
    {
        $namespace = $this->getTopicNamespace($appTopic);

        return isset($this->registry[$namespace][$topic->getId()]);
    }

    public function isPeriodicTimerActive(TopicInterface $appTopic, Topic $topic, string $name): bool
    {
        $namespace = $this->getTopicNamespace($appTopic);

        return isset($this->registry[$namespace][$topic->getId()][$name]);
    }

    public function cancelPeriodicTimer(TopicInterface $appTopic, Topic $topic, string $name): void
    {
        $namespace = $this->getTopicNamespace($appTopic);

        if (!isset($this->registry[$namespace][$topic->getId()][$name])) {
            return;
        }

        $timer = $this->registry[$namespace][$topic->getId()][$name];
        $this->loop->cancelTimer($timer);
        unset($this->registry[$namespace][$topic->getId()][$name]);
    }

    public function clearPeriodicTimer(TopicInterface $appTopic, Topic $topic): void
    {
        $namespace = $this->getTopicNamespace($appTopic);

        foreach ($this->registry[$namespace][$topic->getId()] as $timer) {
            $this->loop->cancelTimer($timer);
        }

        unset($this->registry[$namespace][$topic->getId()]);
    }

    /**
     * @return \ArrayIterator<string, array<string, TimerInterface>>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->registry);
    }

    private function getTopicNamespace(TopicInterface $topic): string
    {
        return spl_object_hash($topic);
    }
}
