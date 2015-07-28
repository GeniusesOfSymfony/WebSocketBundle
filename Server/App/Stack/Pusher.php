<?php

namespace Gos\Bundle\WebSocketBundle\Server\App\Stack;

use Gos\Bundle\NotificationBundle\Pusher\PusherInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\WebSocket\WsServerInterface;

class Pusher implements MessageComponentInterface, WsServerInterface
{
    /** @var  MessageComponentInterface */
    protected $decorated;

    /** @var  PusherInterface */
    protected $pusher;

    /**
     * @param MessageComponentInterface $component
     * @param PusherInterface           $pusher
     */
    public function __construct(MessageComponentInterface $component, PusherInterface $pusher)
    {
        $this->decorated = $component;
        $this->pusher = $pusher;
    }

    /**
     * When a new connection is opened it will be passed to this method.
     *
     * @param ConnectionInterface $conn The socket/connection that just connected to your application
     *
     * @throws \Exception
     */
    public function onOpen(ConnectionInterface $conn)
    {
        return $this->decorated->onOpen($conn);
    }

    /**
     * This is called before or after a socket is closed (depends on how it's closed).  SendMessage to $conn will not result in an error if it has already been closed.
     *
     * @param ConnectionInterface $conn The socket/connection that is closing/closed
     *
     * @throws \Exception
     */
    public function onClose(ConnectionInterface $conn)
    {
        return $this->decorated->onClose($conn);
    }

    /**
     * If there is an error with one of the sockets, or somewhere in the application where an Exception is thrown,
     * the Exception is sent back down the stack, handled by the Server and bubbled back up the application through this method.
     *
     * @param ConnectionInterface $conn
     * @param \Exception          $e
     *
     * @throws \Exception
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        return $this->decorated->onError($conn, $e);
    }

    /**
     * Triggered when a client sends data through the socket.
     *
     * @param \Ratchet\ConnectionInterface $from The socket/connection that sent the message to your application
     * @param string                       $msg  The message received
     *
     * @throws \Exception
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        return $this->decorated->onMessage($from, $msg);
    }

    /**
     * If any component in a stack supports a WebSocket sub-protocol return each supported in an array.
     *
     * @return array
     * @temporary This method may be removed in future version (note that will not break code, just make some code obsolete)
     */
    public function getSubProtocols()
    {
        return $this->getSubProtocols();
    }
}
