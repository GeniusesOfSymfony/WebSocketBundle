<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Gos\Bundle\WebSocketBundle\Pusher\Serializer\MessageSerializer;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AmqpPusher extends AbstractPusher
{
    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @var \AMQPExchange
     */
    private $exchange;

    /**
     * @var AmqpConnectionFactoryInterface
     */
    private $connectionFactory;

    public function __construct(
        WampRouter $router,
        MessageSerializer $serializer,
        AmqpConnectionFactoryInterface $connectionFactory
    ) {
        parent::__construct($router, $serializer);

        $this->connectionFactory = $connectionFactory;
    }

    /**
     * @param string|array $data
     */
    protected function doPush($data, array $context): void
    {
        if (false === $this->connected) {
            $this->connection = $this->connectionFactory->createConnection();
            $this->exchange = $this->connectionFactory->createExchange($this->connection);

            $this->connection->connect();
            $this->setConnected();
        }

        $resolver = new OptionsResolver();

        $resolver->setDefaults(
            [
                'routing_key' => null,
                'publish_flags' => AMQP_NOPARAM,
                'attributes' => [],
            ]
        );

        $context = $resolver->resolve($context);

        $this->exchange->publish(
            $data,
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
