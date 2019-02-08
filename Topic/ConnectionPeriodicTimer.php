<?php

namespace Gos\Bundle\WebSocketBundle\Topic;

use Ratchet\ConnectionInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class ConnectionPeriodicTimer implements \IteratorAggregate, \Countable
{
    /**
     * @var TimerInterface[]
     */
    protected $registry = [];

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @param ConnectionInterface $connection
     * @param LoopInterface       $loop
     */
    public function __construct(ConnectionInterface $connection, LoopInterface $loop)
    {
        $this->connection = $connection;
        $this->loop = $loop;
    }

    /**
     * @param $name
     *
     * @return TimerInterface|bool
     */
    public function getPeriodicTimer($name)
    {
        if (!$this->isPeriodicTimerActive($name)) {
            return false;
        }

        return $this->registry[$this->getTid($name)];
    }

    /**
     * @param string $name
     *
     * @return string
     */
    protected function getTid($name)
    {
        return sha1($this->connection->resourceId . $this->connection->WAMP->sessionId . $name);
    }

    /**
     * @param string    $name
     * @param int|float $timeout
     * @param mixed     $callback
     */
    public function addPeriodicTimer($name, $timeout, $callback)
    {
        $this->registry[$this->getTid($name)] = $this->loop->addPeriodicTimer($timeout, $callback);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isPeriodicTimerActive($name)
    {
        return isset($this->registry[$this->getTid($name)]);
    }

    /**
     * @param string $name
     */
    public function cancelPeriodicTimer($tidOrname)
    {
        if (!isset($this->registry[$tidOrname])) {
            $tid = $this->getTid($tidOrname);
        } else {
            $tid = $tidOrname;
        }

        $timer = $this->registry[$tid];
        $this->loop->cancelTimer($timer);
        unset($this->registry[$tid]);
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->registry);
    }

    /**
     * return int
     */
    public function count()
    {
        return count($this->registry);
    }
}
