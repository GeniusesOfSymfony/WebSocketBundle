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

    public function __construct(Client $client)
    {
        $this->connection = $client;
    }

    /**
     * @param string $data
     * @param array  $context
     */
    protected function doPush($data, array $context)
    {
        if (false === $this->isConnected()) {
            $this->connection->connect('/');
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
