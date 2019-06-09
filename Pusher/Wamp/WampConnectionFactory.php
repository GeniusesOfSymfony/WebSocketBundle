<?php

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Component\WebSocketClient\Wamp\Client;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WampConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $this->resolveConfig($config);
    }

    public function createConnection(): Client
    {
        return new Client(
            $this->config['host'],
            $this->config['port'],
            $this->config['ssl'],
            $this->config['origin']
        );
    }

    private function resolveConfig(array $config): array
    {
        $resolver = new OptionsResolver();

        $resolver->setRequired(
            [
                'host',
                'port',
            ]
        );

        $resolver->setDefaults(
            [
                'ssl' => false,
                'origin' => null,

            ]
        );

        $resolver->setAllowedTypes('host', 'string');
        $resolver->setAllowedTypes('port', 'integer');
        $resolver->setAllowedTypes('ssl', 'boolean');
        $resolver->setAllowedTypes('origin', ['string', 'null']);

        return $resolver->resolve($config);
    }
}
