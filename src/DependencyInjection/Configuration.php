<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\AuthenticationProviderFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
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
        $this->addServerSection($rootNode);
        $this->addPingSection($rootNode);

        $rootNode->children()
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
}
