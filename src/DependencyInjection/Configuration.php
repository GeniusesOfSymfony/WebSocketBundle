<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\AuthenticationProviderFactoryInterface;
use Symfony\Component\Config\Definition\BaseNode;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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

    public const AUTHENTICATION_STORAGE_TYPE_IN_MEMORY = 'in_memory';
    public const AUTHENTICATION_STORAGE_TYPE_PSR_CACHE = 'psr_cache';
    public const AUTHENTICATION_STORAGE_TYPE_SERVICE = 'service';

    /**
     * @var AuthenticationProviderFactoryInterface[]
     */
    private array $authenticationProviderFactories;

    /**
     * @param AuthenticationProviderFactoryInterface[] $authenticationProviderFactories
     */
    public function __construct(array $authenticationProviderFactories)
    {
        $this->authenticationProviderFactories = $authenticationProviderFactories;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('gos_web_socket');

        $rootNode = $treeBuilder->getRootNode();

        $this->addAuthenticationSection($rootNode);
        $this->addClientSection($rootNode);
        $this->addServerSection($rootNode);
        $this->addPingSection($rootNode);
        $this->addPushersSection($rootNode);
        $this->addWebsocketClientSection($rootNode);

        $rootNode->children()
            ->booleanNode('shared_config')
                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0.', '3.9'))
                ->defaultTrue()
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
        ->end();

        return $treeBuilder;
    }

    private function addAuthenticationSection(ArrayNodeDefinition $rootNode): void
    {
        $authenticationNode = $rootNode->children()
            ->arrayNode('authentication')
                ->addDefaultsIfNotSet();

        $this->addAuthenticationProvidersSection($authenticationNode);

        $authenticationNode->children()
                ->arrayNode('storage')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->enumNode('type')
                            ->isRequired()
                            ->defaultValue(self::AUTHENTICATION_STORAGE_TYPE_IN_MEMORY)
                            ->info('The type of storage for the websocket server authentication tokens.')
                            ->values([self::AUTHENTICATION_STORAGE_TYPE_IN_MEMORY, self::AUTHENTICATION_STORAGE_TYPE_PSR_CACHE, self::AUTHENTICATION_STORAGE_TYPE_SERVICE])
                        ->end()
                        ->scalarNode('pool')
                            ->defaultNull()
                            ->info('The cache pool to use when using the PSR cache storage.')
                        ->end()
                        ->scalarNode('id')
                            ->defaultNull()
                            ->info('The service ID to use when using the service storage.')
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $config): bool => ('' === $config['pool'] || null === $config['pool']) && self::AUTHENTICATION_STORAGE_TYPE_PSR_CACHE === $config['type'])
                        ->thenInvalid('A cache pool must be set when using the PSR cache storage')
                    ->end()
                    ->validate()
                        ->ifTrue(static fn (array $config): bool => ('' === $config['id'] || null === $config['id']) && self::AUTHENTICATION_STORAGE_TYPE_SERVICE === $config['type'])
                        ->thenInvalid('A service ID must be set when using the service storage')
                    ->end()
                ->end()
                ->booleanNode('enable_authenticator')
                    ->defaultFalse()
                    ->info('Enables the new authenticator API.')
                    ->validate()
                        ->ifTrue(static fn (bool $enableAuthenticator): bool => !$enableAuthenticator)
                        ->then(static function (bool $enableAuthenticator): void {
                            trigger_deprecation('gos/web-socket-bundle', '3.11', 'Not setting the "gos_web_socket.authentication.enable_authenticator" config option to true is deprecated.');
                        })
                    ->end()
                ->end()
            ->end()
        ->end();
    }

    private function addAuthenticationProvidersSection(ArrayNodeDefinition $authenticationNode): void
    {
        $providerNodeBuilder = $authenticationNode
            ->fixXmlConfig('provider')
            ->children()
                ->arrayNode('providers')
                    // ->requiresAtLeastOneElement() // Will be required as of 4.0
        ;

        foreach ($this->authenticationProviderFactories as $factory) {
            $name = str_replace('-', '_', $factory->getKey());
            $factoryNode = $providerNodeBuilder->children()->arrayNode($name)->canBeUnset();

            $factory->addConfiguration($factoryNode);
        }
    }

    private function addClientSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('client')
                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the new websocket authentication API instead.', '3.11'))
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('session_handler')
                        ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Set the session handler on the session authentication provider instead.', '3.11'))
                        ->info('The service ID of the session handler service used to read session data.')
                    ->end()
                    ->variableNode('firewall')
                        ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Set the firewalls on the session authentication provider instead.', '3.11'))
                        ->defaultValue('ws_firewall')
                        ->info('The name of the security firewall to load the authenticated user data for.')
                    ->end()
                    ->arrayNode('storage')
                        ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.', '3.11'))
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('driver')
                                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.', '3.11'))
                                ->defaultValue('gos_web_socket.client.driver.in_memory')
                                ->info('The service ID of the storage driver to use for storing connection data.')
                            ->end()
                            ->integerNode('ttl')
                                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Configure the TTL on the authentication storage driver instead.', '3.11'))
                                ->defaultValue(900)
                                ->info('The cache TTL (in seconds) for clients in storage.')
                            ->end()
                            ->scalarNode('prefix')
                                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0.', '3.1'))
                                ->defaultValue('')
                            ->end()
                            ->scalarNode('decorator')
                                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the "gos_web_socket.authentication.storage" node instead.', '3.11'))
                                ->info('The service ID of a decorator for the client storage driver.')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addServerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('server')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('host')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->info('The host IP address on the server which connections for the websocket server are accepted.')
                    ->end()
                    ->scalarNode('port')
                        ->isRequired()
                        ->info('The port on the server which connections for the websocket server are accepted.')
                    ->end()
                    ->arrayNode('tls')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->booleanNode('enabled')
                                ->info('Enables native TLS support.')
                                ->defaultFalse()
                            ->end()
                            ->variableNode('options')
                                ->info('An array of options for the TLS context, see https://www.php.net/manual/en/context.ssl.php for available options.')
                                ->defaultValue([])
                            ->end()
                        ->end()
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
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->enumNode('type')
                                            ->defaultNull()
                                            ->values(['closure', 'container', 'glob', 'php', 'xml', 'yaml', null])
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addPingSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('ping')
                ->children()
                    ->arrayNode('services')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')
                                    ->isRequired()
                                    ->cannotBeEmpty()
                                    ->info('The name of the service to ping.')
                                ->end()
                                ->enumNode('type')
                                    ->isRequired()
                                    ->info('The type of the service to be pinged.')
                                    ->values([self::PING_SERVICE_TYPE_DOCTRINE, self::PING_SERVICE_TYPE_PDO])
                                ->end()
                                ->integerNode('interval')
                                    ->defaultValue(20)
                                    ->info('The time (in seconds) between executions of this ping.')
                                    ->min(1)
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    private function addPushersSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('pushers')
                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.', '3.1'))
                ->append($this->addAmqpNode())
                ->append($this->addWampNode())
                ->end()
            ->end()
        ;
    }

    private function addWebsocketClientSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode->children()
            ->arrayNode('websocket_client')
                ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the ratchet/pawl package instead.', '3.4'))
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
        ;
    }

    private function addWampNode(): NodeDefinition
    {
        $node = (new TreeBuilder('wamp'))->getRootNode();

        $node
            ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.', '3.1'))
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
        $node = (new TreeBuilder('amqp'))->getRootNode();

        $node
            ->setDeprecated(...$this->getDeprecationParameters('The child node "%node%" at path "%path%" is deprecated and will be removed in GosWebSocketBundle 4.0. Use the symfony/messenger component instead.', '3.1'))
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
