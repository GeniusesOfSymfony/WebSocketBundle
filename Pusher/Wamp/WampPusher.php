<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Gos\Component\WebSocketClient\Wamp\Client;

class WampPusher extends AbstractPusher
{
    /**
     * @var Client
     */
    protected $connection;

    /**
     * @var WampConnectionFactory
     */
    protected $connectionFactory;

    public function __construct(WampConnectionFactory $connectionFactory)
    {
        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param string|array $data
     */
    protected function doPush($data, array $context): void
    {
        if (false === $this->isConnected()) {
            $this->connection = $this->connectionFactory->createConnection();
            $this->connection->connect();
            $this->setConnected();
        }

        $message = $this->serializer->deserialize($data);

        $this->connection->publish($message->getTopic(), json_encode($message->getData()));
    }

    public function close()
    {
        if (false === $this->isConnected()) {
            return;
        }

        $this->connection->disconnect();
    }
}
