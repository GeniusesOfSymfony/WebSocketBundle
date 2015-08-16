<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Component\WebSocketClient\Wamp\Client;

class WampPusher extends AbstractPusher
{
    /** @var  MessageSerializer */
    protected $serializer;

    /**
     * @param MessageSerializer $serializer
     */
    public function __construct(MessageSerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * @param string $data
     * @param array  $context
     */
    protected function doPush($data, array $context)
    {
        if (false === $this->isConnected()) {
            $config = $this->getConfig();

            $this->connection = new Client($config['host'], $config['port'], $config['ssl'], $config['origin']);
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
