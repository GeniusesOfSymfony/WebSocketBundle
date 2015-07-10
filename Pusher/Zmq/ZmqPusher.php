<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\MessageInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;

class ZmqPusher implements PusherInterface
{
    /** @var  resource */
    protected $connection;

    /** @var  array */
    protected $pusherConfig;

    /** @var bool  */
    protected $isConnected = false;

    /**
     * @param array $pusherConfig
     */
    public function __construct(Array $pusherConfig)
    {
        $this->pusherConfig = $pusherConfig;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->pusherConfig;
    }

    /**
     * @param MessageInterface $message
     */
    public function push(MessageInterface $message)
    {
        $persistent = isset($this->pusherConfig['options']['persistent'])
            ? $this->pusherConfig['options']['persistent']
            : false
        ;

        $context = new \ZMQContext(1, $persistent);
        $client = new \ZMQSocket($context, \ZMQ::SOCKET_PUSH);
        $client->connect($this->pusherConfig['host'].':'.$this->pusherConfig['port']);
        $client->send(json_encode($message));
    }
}
