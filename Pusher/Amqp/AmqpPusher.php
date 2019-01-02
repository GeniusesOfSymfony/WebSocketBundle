<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AmqpPusher extends AbstractPusher
{
    /**
     * @var \AMQPConnection
     */
    protected $connection;

    /**
     * @var \AMQPExchange
     */
    protected $exchange;

    /**
     * @var \AMQPQueue
     */
    protected $queue;

    public function __construct(\AMQPConnection $connection, \AMQPExchange $exchange)
    {
        $this->connection = $connection;
        $this->exchange = $exchange;
    }

    /**
     * @param string $data
     * @param array  $context
     */
    protected function doPush($data, array $context)
    {
        if (false === $this->connected) {
            $this->connection->connect();
            $this->setConnected();
        }

        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'routing_key' => null,
            'publish_flags' => AMQP_NOPARAM,
            'attributes' => array(),
        ]);

        $context = $resolver->resolve($context);

        $this->exchange->publish(
            $data,
            $context['routing_key'],
            $context['publish_flags'],
            $context['attributes']
        );
    }

    public function close()
    {
        if (false === $this->isConnected()) {
            return;
        }

        $this->connection->disconnect();
    }
}
