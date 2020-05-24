<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', WampPusher::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class WampPusher extends AbstractPusher
{
    private ClientInterface $connection;
    private WampConnectionFactoryInterface $connectionFactory;

    public function __construct(
        WampRouter $router,
        SerializerInterface $serializer,
        WampConnectionFactoryInterface $connectionFactory
    ) {
        parent::__construct($router, $serializer);

        $this->connectionFactory = $connectionFactory;
    }

    protected function doPush(Message $message, array $context): void
    {
        if (false === $this->isConnected()) {
            $this->connection = $this->connectionFactory->createConnection();
            $this->connection->connect();
            $this->setConnected();
        }

        $this->connection->publish($message->getTopic(), $this->serializer->serialize($message->getData(), 'json'));
    }

    public function close(): void
    {
        if (false === $this->isConnected()) {
            return;
        }

        $this->connection->disconnect();
    }
}
