<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\Exception\PusherUnsupportedException;

interface AmqpConnectionFactoryInterface
{
    /**
     * @throws PusherUnsupportedException if the pusher is not supported in this environment
     */
    public function createConnection(): \AMQPConnection;

    public function createExchange(\AMQPConnection $connection): \AMQPExchange;

    public function createQueue(\AMQPConnection $connection): \AMQPQueue;

    public function isSupported(): bool;
}
