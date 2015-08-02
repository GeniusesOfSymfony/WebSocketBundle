<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;

class ZmqPusher extends AbstractPusher
{
    /**
     * @param string $data
     */
    protected function doPush($data, array $context)
    {
        $config = $this->getConfig();

        if(false === $this->isConnected()){
            $config = $this->getConfig();

            $context = new \ZMQContext(1, $config['persistent']);
            $this->connection = new \ZMQSocket($context, \ZMQ::SOCKET_PUSH);
            $this->connection->connect($config['protocol'].'://'.$config['host'].':'.$config['port']);
            $this->setConnected();
        }

        $this->connection->send($data);
    }

    public function close()
    {
        if (false === $this->isConnected()) {
            return;
        }

        $config = $this->getConfig();

        $this->connection->disconnect($config['host'].':'.$config['port']);
    }
}
