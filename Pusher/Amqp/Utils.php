<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

class Utils
{
    public static function setupConnection(\AMQPConnection $connection, $config)
    {
        $channel = new \AMQPChannel($connection);

        $exchange = new \AMQPExchange($channel);
        $exchange->setName($config['exchange_name']);
        $exchange->setType(AMQP_EX_TYPE_DIRECT);
        $exchange->setFlags(AMQP_DURABLE);
        $exchange->declareExchange();

        $queue = new \AMQPQueue($channel);
        $queue->setName($config['queue_name']);
        $queue->setFlags(AMQP_DURABLE);
        $queue->declareQueue();

//        $exchange->bind($config['queue_name'], 'gos.websocket.pusher');


        return [
            $channel,
            $exchange,
            $queue,
        ];
    }
}
