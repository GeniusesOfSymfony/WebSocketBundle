<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/**
 * @implements \IteratorAggregate<string, TimerInterface>
 */
class ConnectionPeriodicTimer implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, TimerInterface>
     */
    protected array $registry = [];
    protected ConnectionInterface $connection;
    protected LoopInterface $loop;

    public function __construct(ConnectionInterface $connection, LoopInterface $loop)
    {
        $this->connection = $connection;
        $this->loop = $loop;
    }

    public function getPeriodicTimer(string $name): TimerInterface|bool
    {
        if (!$this->isPeriodicTimerActive($name)) {
            return false;
        }

        return $this->registry[$this->getTid($name)];
    }

    protected function getTid(string $name): string
    {
        return sha1($this->connection->resourceId.$this->connection->WAMP->sessionId.$name);
    }

    public function addPeriodicTimer(string $name, int|float $timeout, callable $callback): void
    {
        $this->registry[$this->getTid($name)] = $this->loop->addPeriodicTimer($timeout, $callback);
    }

    public function isPeriodicTimerActive(string $name): bool
    {
        return isset($this->registry[$this->getTid($name)]);
    }

    public function cancelPeriodicTimer(string $tidOrName): void
    {
        if (!isset($this->registry[$tidOrName])) {
            $tid = $this->getTid($tidOrName);
        } else {
            $tid = $tidOrName;
        }

        $timer = $this->registry[$tid];
        $this->loop->cancelTimer($timer);
        unset($this->registry[$tid]);
    }

    /**
     * @return \ArrayIterator<string, TimerInterface>
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->registry);
    }

    public function count(): int
    {
        return \count($this->registry);
    }
}
