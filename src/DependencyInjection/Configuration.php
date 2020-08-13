<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class Configuration implements ConfigurationInterface
{
    private const DEFAULT_TTL = 900;
    private const DEFAULT_CLIENT_STORAGE_SERVICE = 'gos_web_socket.client.driver.in_memory';
    private const DEFAULT_FIREWALL = 'ws_firewall';
    private const DEFAULT_ORIGIN_CHECKER = false;
    private const DEFAULT_KEEPALIVE_PING = false;
    private const DEFAULT_KEEPALIVE_INTERVAL = 30;
    public const PING_SERVICE_TYPE_DOCTRINE = 'doctrine';
    public const PING_SERVICE_TYPE_PDO = 'pdo';

    /**
     * @deprecated to be removed in 4.0
     */
    private const DEFAULT_PREFIX = '';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('gos_web_socket');

        $treeBuilder->getRootNode()->children()
            ->arrayNode('client')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('session_handler')
                        ->example('@session.handler.pdo')
                    ->end()
                    ->variableNode('firewall')
                        ->example('secured_area')
                        ->defaultValue(static::DEFAULT_FIREWALL)
                    ->end()
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('driver')
                                ->defaultValue(static::DEFAULT_CLIENT_STORAGE_SERVICE)
                                ->example(static::DEFAULT_CLIENT_STORAGE_SERVICE)
                            ->end()
                            ->integerNode('ttl')
                                ->defaultValue(static::DEFAULT_TTL)
                                ->example(3600)
                            ->end()
                            ->scalarNode('prefix')
                                ->setDeprecated(...$this->getDeprecationParameters('The "%node%" node is deprecated and will be removed in GosWebSocketBundle 4.0.', '3.1'))
                                ->defaultValue(static::DEFAULT_PREFIX)
                                ->example('client')
                            ->end()
                            ->scalarNode('decorator')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->booleanNode('shared_config')
                ->defaultTrue()
            ->end()
            ->arrayNode('server')
                ->children()
                    ->scalarNode('host')
                        ->example('127.0.0.1')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->scalarNode('port')
                        ->example(8080)
                        ->isRequired()
                    ->end()
                    ->booleanNode('origin_check')
                        ->defaultValue(static::DEFAULT_ORIGIN_CHECKER)
                        ->example('true')
                    ->end()
                    ->booleanNode('keepalive_ping')
                        ->defaultValue(static::DEFAULT_KEEPALIVE_PING)
                        ->example('true')
                        ->info('Flag indicating a keepalive ping should be enabled on the server')
                    ->end()
                    ->integerNode('keepalive_interval')
                        ->defaultValue(static::DEFAULT_KEEPALIVE_INTERVAL)
                        ->example(30)
                        ->info('The time in seconds between each keepalive ping')
                    ->end()
                    ->arrayNode('router')
                        ->children()
                            ->arrayNode('resources')
                                ->beforeNormalization()
                                    ->ifTrue(static function ($v) {
                                        foreach ($v as $resource) {
                                            if (!\is_array($resource)) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    })
                                    ->then(static function ($v) {
                                        $resources = [];

                                        foreach ($v as $resource) {
                                            if (\is_array($resource)) {
                                                $resources[] = $resource;
                                            } else {
                                                $resources[] = [
                                                    'resource' => $resource,
                                                ];
                                            }
                                        }

                                        return $resources;
                                    })
                                ->end()
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('resource')
                                            ->cannotBeEmpty()
                                            ->isRequired()
                                        ->end()
                                        ->enumNode('type')
                                            ->values(['closure', 'container', 'glob', 'php', 'xml', 'yaml', null])
                                            ->defaultNull()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('origins')
                ->scalarPrototype()
                ->validate()
                    ->ifInArray(['localhost', '127.0.0.1'])
                        ->thenInvalid('%s is added by default')
                    ->end()
                ->end()
            ->end()
            ->arrayNode('ping')
                ->children()
                    ->arrayNode('services')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')
                                    ->info('The name of the service to ping')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->enumNode('type')
                                    ->info('The type of the service to be pinged; valid options are "doctrine" and "pdo"')
                                    ->isRequired()
                                    ->values([self::PING_SERVICE_TYPE_DOCTRINE, self::PING_SERVICE_TYPE_PDO])
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('websocket_client')
                ->setDeprecated(...$this->getDeprecationParameters('The "%node%" node is deprecated and will be removed in GosWebSocketBundle 4.0. Use the ratchet/pawl package instead.', '3.4'))
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
                ->children()
                    ->scalarNode('host')
                        ->example('127.0.0.1')
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('port')
                        ->example(1337)
                        ->isRequired()
                    ->end()
                    ->booleanNode('ssl')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('origin')
                        ->defaultNull()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('pushers')
                ->setDeprecated(...$this->getDeprecationParameters('The "%node%" node is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.', '3.1'))
                ->append($this->addAmqpNode())
                ->append($this->addWampNode())
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

    private function addWampNode(): NodeDefinition
    {
        $builder = new TreeBuilder('wamp');

        $node = $builder->getRootNode();

        $node
            ->setDeprecated(...$this->getDeprecationParameters('The "%node%" node is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.', '3.1'))
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
                ->scalarNode('host')
                    ->example('127.0.0.1')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
                    ->example(1337)
                    ->isRequired()
                ->end()
                ->booleanNode('ssl')
                    ->defaultFalse()
                ->end()
                ->scalarNode('origin')
                    ->defaultNull()
                ->end()
            ->end();

        return $node;
    }

    private function addAmqpNode(): NodeDefinition
    {
        $builder = new TreeBuilder('amqp');

        $node = $builder->getRootNode();

        $node
            ->setDeprecated(...$this->getDeprecationParameters('The "%node%" node is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.', '3.1'))
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
            ->children()
                ->scalarNode('host')
                    ->example('127.0.0.1')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
                    ->example(5672)
                    ->isRequired()
                ->end()
                ->scalarNode('login')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('password')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('vhost')
                    ->defaultValue('/')
                ->end()
                ->integerNode('read_timeout')
                    ->defaultValue(0)
                ->end()
                ->integerNode('write_timeout')
                    ->defaultValue(0)
                ->end()
                ->integerNode('connect_timeout')
                    ->defaultValue(0)
                ->end()
                ->scalarNode('queue_name')
                    ->defaultValue('gos_websocket')
                ->end()
                ->scalarNode('exchange_name')
                    ->defaultValue('gos_websocket_exchange')
                ->end()
            ->end();

        return $node;
    }

    private function getDeprecationParameters(string $message, string $version): array
    {
        if (method_exists(BaseNode::class, 'getDeprecation')) {
            return ['gos/web-socket-bundle', $version, $message];
        }

        return [$message];
    }
}
