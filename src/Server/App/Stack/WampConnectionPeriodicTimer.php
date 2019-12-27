<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\WebSocketBundle\Topic\ConnectionPeriodicTimer;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

class WampConnectionPeriodicTimer implements MessageComponentInterface, WsServerInterface
{
    protected MessageComponentInterface $decorated;
    protected LoopInterface $loop;

    public function __construct(MessageComponentInterface $component, LoopInterface $loop)
    {
        $this->decorated = $component;
        $this->loop = $loop;
    }

    /**
     * @return mixed
     */
    public function onOpen(ConnectionInterface $connection)
    {
        $connection->PeriodicTimer = new ConnectionPeriodicTimer($connection, $this->loop);

        return $this->decorated->onOpen($connection);
    }

    /**
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
     * @return mixed
     */
    public function onError(ConnectionInterface $connection, \Exception $e)
    {
        return $this->decorated->onError($connection, $e);
    }

    /**
     * @param string $msg
     *
     * @return mixed
     */
    public function onMessage(ConnectionInterface $connection, $msg)
    {
        return $this->decorated->onMessage($connection, $msg);
    }

    /**
     * @return array
     */
    public function getSubProtocols()
    {
        if ($this->decorated instanceof WsServerInterface) {
            return $this->decorated->getSubProtocols();
        }

        return [];
    }
}
