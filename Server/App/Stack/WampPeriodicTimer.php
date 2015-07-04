<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

/**
 * Wrap WampServer nicely.
 */
class WampPeriodicTimer implements MessageComponentInterface, WsServerInterface
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

    public function onOpen(ConnectionInterface $connection)
    {
        $connection->PeriodicTimer = new TopicPeriodicTimer($connection, $this->loop);

        return $this->decorated->onOpen($connection);
    }

    public function onClose(ConnectionInterface $connection)
    {
        /** @var TimerInterface $timer */
        foreach ($connection->PeriodicTimer as $tid => $timer) {
            $connection->PeriodicTimer->cancelPeriodicTimer($tid);
        }

        return $this->decorated->onClose($connection);
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        return $this->decorated->onError($connection, $e);
    }

    public function onMessage(ConnectionInterface $connection, $msg)
    {
        return $this->decorated->onMessage($connection, $msg);
    }

    public function getSubProtocols()
    {
        return $this->decorated->getSubProtocols();
    }
}
