<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Topic\ConnectionPeriodicTimer;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

/**
 * Wrap WampServer nicely.
 */
class WampConnectionPeriodicTimer implements MessageComponentInterface, WsServerInterface
{
    /**
     * @var MessageComponentInterface
     */
    protected $decorated;

    /**
     * @var LoopInterface
     */
    protected $loop;

    /**
     * @param MessageComponentInterface $component
     */
    public function __construct(MessageComponentInterface $component, LoopInterface $loop)
    {
        $this->decorated = $component;
        $this->loop = $loop;
        $this->timerRegistry = [];
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return mixed
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $connection->PeriodicTimer = new ConnectionPeriodicTimer($connection, $this->loop);

        return $this->decorated->onOpen($connection);
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return mixed
     */
    public function onClose(ConnectionInterface $connection)
    {
        /** @var TimerInterface $timer */
        foreach ($connection->PeriodicTimer as $tid => $timer) {
            $connection->PeriodicTimer->cancelPeriodicTimer($tid);
        }

        return $this->decorated->onClose($connection);
    }

    /**
     * @param ConnectionInterface $connection
     * @param \Exception          $e
     *
     * @return mixed
     */
    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        return $this->decorated->onError($connection, $e);
    }

    /**
     * @param ConnectionInterface $connection
     * @param string              $msg
     *
     * @return mixed
     */
    public function onMessage(ConnectionInterface $connection, $msg)
    {
        return $this->decorated->onMessage($connection, $msg);
    }

    /**
     * @return mixed
     */
    public function getSubProtocols()
    {
        return $this->decorated->getSubProtocols();
    }
}
