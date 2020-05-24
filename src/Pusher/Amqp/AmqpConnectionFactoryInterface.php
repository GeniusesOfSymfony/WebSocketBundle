<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\Exception\PusherUnsupportedException;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" interface is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', AmqpConnectionFactoryInterface::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
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
