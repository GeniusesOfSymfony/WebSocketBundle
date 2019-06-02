<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Zmq;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;

class ZmqPusher extends AbstractPusher
{
    /**
     * @var \ZMQSocket
     */
    protected $connection;

    /**
     * @var ZmqConnectionFactory
     */
    protected $connectionFactory;

    public function __construct(ZmqConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param string $data
     * @param array  $context
     */
    protected function doPush($data, array $context)
    {
        if (false === $this->isConnected()) {
            $this->connection = $this->connectionFactory->createConnection();
            $this->connection->connect($this->connectionFactory->buildConnectionDsn());
            $this->setConnected();
        }

        $this->connection->send($data);
    }

    public function close()
    {
        if (false === $this->isConnected()) {
            return;
        }

        $this->connection->disconnect($this->connectionFactory->buildConnectionDsn());
    }
}
