<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\Exception\PusherUnsupportedException;
use React\EventLoop\LoopInterface;
use React\ZMQ\SocketWrapper;

interface ZmqConnectionFactoryInterface
{
    public function buildConnectionDsn(): string;

    /**
     * @throws PusherUnsupportedException if the pusher is not supported in this environment
     */
    public function createConnection(): \ZMQSocket;

    /**
     * @throws PusherUnsupportedException if the pusher is not supported in this environment
     */
    public function createWrappedConnection(LoopInterface $loop, int $socketType = 7 /*\ZMQ::SOCKET_PULL*/): SocketWrapper;

    public function isSupported(): bool;
}
