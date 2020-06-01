<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Pusher\Wamp;

use Gos\Component\WebSocketClient\Wamp\Client;
use Gos\Component\WebSocketClient\Wamp\ClientFactory;
use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\OptionsResolver\OptionsResolver;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use "%s" instead.', WampConnectionFactory::class, ClientFactory::class);

/**
 * @deprecated to be removed in 4.0, use Gos\Component\WebSocketClient\Wamp\ClientFactory from the gos/websocket-client package instead
 */
final class WampConnectionFactory implements WampConnectionFactoryInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $this->resolveConfig($config);
    }

    public function createConnection(): ClientInterface
    {
        $client = new Client(
            $this->config['host'],
            $this->config['port'],
            $this->config['ssl'],
            $this->config['origin']
        );

        if (null !== $this->logger) {
            $client->setLogger($this->logger);
        }

        return $client;
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
        $resolver->setAllowedTypes('port', ['string', 'integer']);
        $resolver->setAllowedTypes('ssl', 'boolean');
        $resolver->setAllowedTypes('origin', ['string', 'null']);

        return $resolver->resolve($config);
    }
}
