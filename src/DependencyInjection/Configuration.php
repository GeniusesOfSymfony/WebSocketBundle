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
    public const PING_SERVICE_TYPE_DOCTRINE = 'doctrine';
    public const PING_SERVICE_TYPE_PDO = 'pdo';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('gos_web_socket');

        $treeBuilder->getRootNode()->children()
            ->arrayNode('client')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('session_handler')
                        ->info('The service ID of the session handler service used to read session data.')
                    ->end()
                    ->variableNode('firewall')
                        ->defaultValue('ws_firewall')
                        ->info('The name of the security firewall to load the authenticated user data for.')
                    ->end()
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('driver')
                                ->defaultValue('gos_web_socket.client.driver.in_memory')
                                ->info('The service ID of the storage driver to use for storing connection data.')
                            ->end()
                            ->integerNode('ttl')
                                ->defaultValue(900)
                                ->info('The cache TTL (in seconds) for clients in storage.')
                            ->end()
                            ->scalarNode('prefix')
                                ->setDeprecated(...$this->getDeprecationParameters('The "%node%" node is deprecated and will be removed in GosWebSocketBundle 4.0.', '3.1'))
                                ->defaultValue('')
                            ->end()
                            ->scalarNode('decorator')
                                ->info('The service ID of a decorator for the client storage driver.')
                            ->end()
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
                        ->info('The host IP address on the server which connections for the websocket server are accepted.')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->scalarNode('port')
                        ->info('The port on the server which connections for the websocket server are accepted.')
                        ->isRequired()
                    ->end()
                    ->booleanNode('origin_check')
                        ->defaultFalse()
                        ->info('Enables checking the Origin header of websocket connections for allowed values.')
                    ->end()
                    ->booleanNode('ip_address_check')
                        ->defaultFalse()
                        ->info('Enables checking the originating IP address of websocket connections for blocked addresses.')
                    ->end()
                    ->booleanNode('keepalive_ping')
                        ->defaultFalse()
                        ->info('Flag indicating a keepalive ping should be enabled on the server.')
                    ->end()
                    ->integerNode('keepalive_interval')
                        ->defaultValue(30)
                        ->info('The time in seconds between each keepalive ping.')
                    ->end()
                    ->arrayNode('router')
                        ->children()
                            ->arrayNode('resources')
                                ->beforeNormalization()
                                    ->ifTrue(static function ($v): bool {
                                        foreach ($v as $resource) {
                                            if (!\is_array($resource)) {
                                                return true;
                                            }
                                        }

                                        return false;
                                    })
                                    ->then(static function ($v): array {
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
                ->info('A list of origins allowed to connect to the websocket server, must match the value from the "Origin" header of the HTTP request.')
                ->scalarPrototype()
                ->validate()
                    ->ifInArray(['localhost', '127.0.0.1'])
                        ->thenInvalid('%s is added by default')
                    ->end()
                ->end()
            ->end()
            ->arrayNode('blocked_ip_addresses')
                ->info('A list of IP addresses which are not allowed to connect to the websocket server.')
                ->scalarPrototype()
                ->end()
            ->end()
            ->arrayNode('ping')
                ->children()
                    ->arrayNode('services')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')
                                    ->info('The name of the service to ping.')
                                    ->cannotBeEmpty()
                                    ->isRequired()
                                ->end()
                                ->enumNode('type')
                                    ->info('The type of the service to be pinged.')
                                    ->isRequired()
                                    ->values([self::PING_SERVICE_TYPE_DOCTRINE, self::PING_SERVICE_TYPE_PDO])
                                ->end()
                                ->integerNode('interval')
                                    ->info('The time (in seconds) between executions of this ping.')
                                    ->defaultValue(20)
                                    ->min(1)
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
                        ->isRequired()
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('port')
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
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
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
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
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
