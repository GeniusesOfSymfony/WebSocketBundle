<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Gos\Bundle\WebSocketBundle\Pusher\Message;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\SerializerInterface;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', AmqpPusher::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class AmqpPusher extends AbstractPusher
{
    private \AMQPConnection $connection;
    private \AMQPExchange $exchange;
    private AmqpConnectionFactoryInterface $connectionFactory;

    public function __construct(
        WampRouter $router,
        SerializerInterface $serializer,
        AmqpConnectionFactoryInterface $connectionFactory
    ) {
        parent::__construct($router, $serializer);

        $this->connectionFactory = $connectionFactory;
    }

    protected function doPush(Message $message, array $context): void
    {
        if (false === $this->connected) {
            $this->connection = $this->connectionFactory->createConnection();
            $this->connection->connect();

            $this->exchange = $this->connectionFactory->createExchange($this->connection);

            $this->setConnected();
        }

        $resolver = new OptionsResolver();

        $resolver->setDefaults(
            [
                'routing_key' => '',
                'publish_flags' => AMQP_NOPARAM,
                'attributes' => [],
            ]
        );

        $context = $resolver->resolve($context);

        $this->exchange->publish(
            $this->serializer->serialize($message, 'json'),
            $context['routing_key'],
            $context['publish_flags'],
            $context['attributes']
        );
    }

    public function close(): void
    {
        if (false === $this->isConnected()) {
            return;
        }

        $this->connection->disconnect();
    }
}
