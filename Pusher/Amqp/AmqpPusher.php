<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Amqp;

use Gos\Bundle\WebSocketBundle\Pusher\AbstractPusher;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AmqpPusher extends AbstractPusher
{
    /** @var  \AMQPExchange */
    protected $exchange;

    /** @var  \AMQPQueue */
    protected $queue;

    /**
     * @param string $data
     * @param array  $context
     */
    protected function doPush($data, array $context)
    {
        $config = $this->getConfig();

        if (false === $this->connected) {
            if (!extension_loaded('amqp')) {
                throw new \RuntimeException(sprintf(
                    '%s pusher require %s php extension',
                    get_class($this),
                    'amqp'
                ));
            }

            $this->connection = new \AMQPConnection($config);
            $this->connection->connect();

            list(, $this->exchange) = Utils::setupConnection($this->connection, $config);

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
