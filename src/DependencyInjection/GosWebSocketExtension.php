<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\StorageDriverInterface;
use Gos\Bundle\WebSocketBundle\Client\Auth\WebsocketAuthenticationProviderInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Client\ClientStorageInterface;
use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\AuthenticationProviderFactoryInterface;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactory;
use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactory;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Component\WebSocketClient\Wamp\Client;
use Gos\Component\WebSocketClient\Wamp\ClientFactory;
use Gos\Component\WebSocketClient\Wamp\ClientFactoryInterface;
use Gos\Component\WebSocketClient\Wamp\ClientInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class GosWebSocketExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    private const DEPRECATED_ALIASES = [
        ClientManipulatorInterface::class => '3.11',
        ClientStorageInterface::class => '3.11',
        DriverInterface::class => '3.11',
        PusherRegistry::class => '3.1',
        ServerPushHandlerRegistry::class => '3.1',
        WebsocketAuthenticationProviderInterface::class => '3.11',
        'gos_web_socket.session_handler' => '3.11',
    ];

    private const DEPRECATED_SERVICES = [
        'gos_web_socket.client.authentication.websocket_provider' => '3.11',
        'gos_web_socket.client.driver.doctrine_cache' => '3.4',
        'gos_web_socket.client.driver.in_memory' => '3.11',
        'gos_web_socket.client.driver.symfony_cache' => '3.11',
        'gos_web_socket.client.manipulator' => '3.11',
        'gos_web_socket.client.storage' => '3.11',
        'gos_web_socket.data_collector.websocket' => '3.1',
        'gos_web_socket.event_listener.close_pusher_connections' => '3.1',
        'gos_web_socket.event_listener.register_push_handlers' => '3.1',
        'gos_web_socket.pusher.amqp' => '3.1',
        'gos_web_socket.pusher.amqp.push_handler' => '3.1',
        'gos_web_socket.pusher.wamp' => '3.1',
        'gos_web_socket.registry.pusher' => '3.1',
        'gos_web_socket.registry.server_push_handler' => '3.1',
        'gos_web_socket.server.entry_point' => '3.7',
    ];

    /**
     * @var AuthenticationProviderFactoryInterface[]
     */
    private array $authenticationProviderFactories = [];

    public function addAuthenticationProviderFactory(AuthenticationProviderFactoryInterface $factory): void
    {
        $this->authenticationProviderFactories[] = $factory;
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration($this->authenticationProviderFactories);
    }

    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.yaml');
        $loader->load('aliases.yaml');

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $container->setParameter('gos_web_socket.shared_config', $mergedConfig['shared_config']);

        $this->registerAuthenticationConfiguration($mergedConfig, $container);
        $this->registerClientConfiguration($mergedConfig, $container);
        $this->registerServerConfiguration($mergedConfig, $container);
        $this->registerOriginsConfiguration($mergedConfig, $container);
        $this->registerBlockedIpAddressesConfiguration($mergedConfig, $container);
        $this->registerPingConfiguration($mergedConfig, $container);
        $this->registerPushersConfiguration($mergedConfig, $container);
        $this->registerWebsocketClientConfiguration($mergedConfig, $container);

        $this->maybeEnableAuthenticatorApi($mergedConfig, $container);

        $this->markAliasesDeprecated($container);
        $this->markServicesDeprecated($container);
    }

    private function markAliasesDeprecated(ContainerBuilder $container): void
    {
        $usesSymfony51Api = method_exists(Alias::class, 'getDeprecation');

        foreach (self::DEPRECATED_ALIASES as $aliasId => $deprecatedSince) {
            if (!$container->hasAlias($aliasId)) {
                continue;
            }

            $alias = $container->getAlias($aliasId);

            if ($usesSymfony51Api) {
                $alias->setDeprecated(
                    'gos/web-socket-bundle',
                    $deprecatedSince,
                    'The "%alias_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0.'
                );
            } else {
                $alias->setDeprecated(
                    true,
                    'The "%alias_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0.'
                );
            }
        }
    }

    private function markServicesDeprecated(ContainerBuilder $container): void
    {
        $usesSymfony51Api = method_exists(Definition::class, 'getDeprecation');

        foreach (self::DEPRECATED_SERVICES as $serviceId => $deprecatedSince) {
            if (!$container->hasDefinition($serviceId)) {
                continue;
            }

            $service = $container->getDefinition($serviceId);

            if ($usesSymfony51Api) {
                $service->setDeprecated(
                    'gos/web-socket-bundle',
                    $deprecatedSince,
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0.'
                );
            } else {
                $service->setDeprecated(
                    true,
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0.'
                );
            }
        }
    }

    private function maybeEnableAuthenticatorApi(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!$mergedConfig['authentication']['enable_authenticator']) {
            return;
        }

        $container->getDefinition('gos_web_socket.event_listener.client')
            ->replaceArgument(0, new Reference('gos_web_socket.authentication.token_storage'))
            ->replaceArgument(1, new Reference('gos_web_socket.authentication.authenticator'));

        $container->getDefinition('gos_web_socket.server.application.wamp')
            ->replaceArgument(3, new Reference('gos_web_socket.authentication.token_storage'));

        $container->removeDefinition('gos_web_socket.client.authentication.websocket_provider');
        $container->removeDefinition('gos_web_socket.client.driver.doctrine_cache');
        $container->removeDefinition('gos_web_socket.client.driver.in_memory');
        $container->removeDefinition('gos_web_socket.client.driver.symfony_cache');
        $container->removeDefinition('gos_web_socket.client.manipulator');
        $container->removeDefinition('gos_web_socket.client.storage');
    }

    private function registerAuthenticationConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $authenticators = [];

        if (isset($mergedConfig['authentication']['providers'])) {
            foreach ($this->authenticationProviderFactories as $factory) {
                $key = str_replace('-', '_', $factory->getKey());

                if (!isset($mergedConfig['authentication']['providers'][$key])) {
                    continue;
                }

                $authenticators[] = new Reference($factory->createAuthenticationProvider($container, $mergedConfig['authentication']['providers'][$key]));
            }
        }

        $container->getDefinition('gos_web_socket.authentication.authenticator')
            ->replaceArgument(0, new IteratorArgument($authenticators));

        $storageId = null;

        switch ($mergedConfig['authentication']['storage']['type']) {
            case Configuration::AUTHENTICATION_STORAGE_TYPE_IN_MEMORY:
                $storageId = 'gos_web_socket.authentication.storage.driver.in_memory';

                break;

            case Configuration::AUTHENTICATION_STORAGE_TYPE_PSR_CACHE:
                $storageId = 'gos_web_socket.authentication.storage.driver.psr_cache';

                $container->getDefinition($storageId)
                    ->replaceArgument(0, new Reference($mergedConfig['authentication']['storage']['pool']));

                break;

            case Configuration::AUTHENTICATION_STORAGE_TYPE_SERVICE:
                $storageId = $mergedConfig['authentication']['storage']['id'];

                break;
        }

        $container->setAlias('gos_web_socket.authentication.storage.driver', $storageId);
        $container->setAlias(StorageDriverInterface::class, $storageId);
    }

    private function registerClientConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        // @deprecated to be removed in 4.0, authentication API has been replaced
        $container->setParameter('gos_web_socket.client.storage.ttl', $mergedConfig['client']['storage']['ttl']);

        // @deprecated to be removed in 4.0, authentication API has been replaced
        $container->setParameter('gos_web_socket.firewall', (array) $mergedConfig['client']['firewall']);

        // @deprecated to be removed in 4.0, parameter is unused
        $container->setParameter('gos_web_socket.client.storage.prefix', $mergedConfig['client']['storage']['prefix']);

        // @deprecated to be removed in 4.0, session handler config is moved
        if (isset($mergedConfig['client']['session_handler'])) {
            $sessionHandlerId = $mergedConfig['client']['session_handler'];

            $container->getDefinition('gos_web_socket.server.builder')
                ->addMethodCall('setSessionHandler', [new Reference($sessionHandlerId)]);

            $container->setAlias('gos_web_socket.session_handler', $sessionHandlerId);
        }

        // @deprecated to be removed in 4.0, authentication API has been replaced
        if (isset($mergedConfig['client']['storage']['driver'])) {
            $driverId = $mergedConfig['client']['storage']['driver'];
            $storageDriver = $driverId;

            if (isset($mergedConfig['client']['storage']['decorator'])) {
                $decoratorId = $mergedConfig['client']['storage']['decorator'];
                $container->getDefinition($decoratorId)
                    ->addArgument(new Reference($driverId));

                $storageDriver = $decoratorId;
            }

            // Alias the DriverInterface in use for autowiring
            $container->setAlias(DriverInterface::class, new Alias($storageDriver));

            $container->getDefinition('gos_web_socket.client.storage')
                ->replaceArgument(0, new Reference($storageDriver));
        }
    }

    private function registerServerConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('gos_web_socket.server.port', $mergedConfig['server']['port']);
        $container->setParameter('gos_web_socket.server.host', $mergedConfig['server']['host']);
        $container->setParameter('gos_web_socket.server.tls.enabled', $mergedConfig['server']['tls']['enabled']);
        $container->setParameter('gos_web_socket.server.tls.options', $mergedConfig['server']['tls']['options']);
        $container->setParameter('gos_web_socket.server.origin_check', $mergedConfig['server']['origin_check']);
        $container->setParameter('gos_web_socket.server.ip_address_check', $mergedConfig['server']['ip_address_check']);
        $container->setParameter('gos_web_socket.server.keepalive_ping', $mergedConfig['server']['keepalive_ping']);
        $container->setParameter('gos_web_socket.server.keepalive_interval', $mergedConfig['server']['keepalive_interval']);

        $routerConfig = [];

        foreach (($mergedConfig['server']['router']['resources'] ?? []) as $resource) {
            if (\is_array($resource)) {
                $routerConfig[] = $resource;
            } else {
                $routerConfig[] = [
                    'resource' => $resource,
                    'type' => null,
                ];
            }
        }

        $container->setParameter('gos_web_socket.router_resources', $routerConfig);
    }

    private function registerOriginsConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $originsRegistryDef = $container->getDefinition('gos_web_socket.registry.origins');

        foreach ($mergedConfig['origins'] as $origin) {
            $originsRegistryDef->addMethodCall('addOrigin', [$origin]);
        }
    }

    private function registerBlockedIpAddressesConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->setParameter('gos_web_socket.blocked_ip_addresses', $mergedConfig['blocked_ip_addresses']);
    }

    /**
     * @throws InvalidArgumentException if an unsupported ping service type is given
     */
    private function registerPingConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!isset($mergedConfig['ping'])) {
            return;
        }

        foreach ((array) $mergedConfig['ping']['services'] as $pingService) {
            $serviceId = $pingService['name'];

            switch ($pingService['type']) {
                case Configuration::PING_SERVICE_TYPE_DOCTRINE:
                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.doctrine');
                    $definition->addArgument(new Reference($serviceId));
                    $definition->addArgument($pingService['interval']);
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.doctrine.'.$serviceId, $definition);

                    break;

                case Configuration::PING_SERVICE_TYPE_PDO:
                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.pdo');
                    $definition->addArgument(new Reference($serviceId));
                    $definition->addArgument($pingService['interval']);
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.pdo.'.$serviceId, $definition);

                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unsupported ping service type "%s"', $pingService['type']));
            }
        }
    }

    private function registerPushersConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!isset($mergedConfig['pushers'])) {
            // Remove all of the pushers
            foreach (['gos_web_socket.pusher.amqp', 'gos_web_socket.pusher.wamp'] as $pusher) {
                $container->removeDefinition($pusher);
            }

            foreach (['gos_web_socket.pusher.amqp.push_handler'] as $pusher) {
                $container->removeDefinition($pusher);
            }

            return;
        }

        $usesSymfony51Api = method_exists(Definition::class, 'getDeprecation');

        if (isset($mergedConfig['pushers']['amqp']) && $this->isConfigEnabled($container, $mergedConfig['pushers']['amqp'])) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $mergedConfig['pushers']['amqp'];
            unset($factoryConfig['enabled']);

            $connectionFactoryDef = new Definition(
                AmqpConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPublic(false);

            if ($usesSymfony51Api) {
                $connectionFactoryDef->setDeprecated(
                    'gos/web-socket-bundle',
                    '3.1',
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the symfony/messenger component instead.'
                );
            } else {
                $connectionFactoryDef->setDeprecated(
                    true,
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the symfony/messenger component instead.'
                );
            }

            $container->setDefinition('gos_web_socket.pusher.amqp.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.pusher.amqp')
                ->setArgument(2, new Reference('gos_web_socket.pusher.amqp.connection_factory'));

            $container->getDefinition('gos_web_socket.pusher.amqp.push_handler')
                ->setArgument(3, new Reference('gos_web_socket.pusher.amqp.connection_factory'));
        } else {
            $container->removeDefinition('gos_web_socket.pusher.amqp');
            $container->removeDefinition('gos_web_socket.pusher.amqp.push_handler');
        }

        if (isset($mergedConfig['pushers']['wamp']) && $this->isConfigEnabled($container, $mergedConfig['pushers']['wamp'])) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $mergedConfig['pushers']['wamp'];
            unset($factoryConfig['enabled']);

            $connectionFactoryDef = new Definition(
                WampConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPublic(false);
            $connectionFactoryDef->addTag('monolog.logger', ['channel' => 'websocket']);
            $connectionFactoryDef->addMethodCall('setLogger', [new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);

            if ($usesSymfony51Api) {
                $connectionFactoryDef->setDeprecated(
                    'gos/web-socket-bundle',
                    '3.1',
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the symfony/messenger component instead.'
                );
            } else {
                $connectionFactoryDef->setDeprecated(
                    true,
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the symfony/messenger component instead.'
                );
            }

            $container->setDefinition('gos_web_socket.pusher.wamp.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.pusher.wamp')
                ->setArgument(2, new Reference('gos_web_socket.pusher.wamp.connection_factory'));
        } else {
            $container->removeDefinition('gos_web_socket.pusher.wamp');
        }
    }

    private function registerWebsocketClientConfiguration(array $mergedConfig, ContainerBuilder $container): void
    {
        if (!$mergedConfig['websocket_client']['enabled']) {
            return;
        }

        $usesSymfony51Api = method_exists(Definition::class, 'getDeprecation');

        // Pull the 'enabled' field out of the client's config
        $factoryConfig = $mergedConfig['websocket_client'];
        unset($factoryConfig['enabled']);

        $clientFactoryDef = new Definition(
            ClientFactory::class,
            [
                $factoryConfig,
            ]
        );
        $clientFactoryDef->setPublic(false);
        $clientFactoryDef->addTag('monolog.logger', ['channel' => 'websocket']);
        $clientFactoryDef->addMethodCall('setLogger', [new Reference('logger', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);

        if ($usesSymfony51Api) {
            $clientFactoryDef->setDeprecated(
                'gos/web-socket-bundle',
                '3.4',
                'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        } else {
            $clientFactoryDef->setDeprecated(
                true,
                'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        }

        $container->setDefinition('gos_web_socket.websocket_client_factory', $clientFactoryDef);

        $alias = new Alias('gos_web_socket.websocket_client_factory');

        if ($usesSymfony51Api) {
            $alias->setDeprecated(
                'gos/web-socket-bundle',
                '3.4',
                'The "%alias_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        } else {
            $alias->setDeprecated(
                true,
                'The "%alias_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        }

        foreach ([ClientFactory::class, ClientFactoryInterface::class] as $aliasedObject) {
            $container->setAlias($aliasedObject, $alias);
        }

        $clientDef = new Definition(Client::class);
        $clientDef->setFactory([new Reference('gos_web_socket.websocket_client_factory'), 'createConnection']);

        if ($usesSymfony51Api) {
            $clientDef->setDeprecated(
                'gos/web-socket-bundle',
                '3.4',
                'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        } else {
            $clientDef->setDeprecated(
                true,
                'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        }

        $container->setDefinition('gos_web_socket.websocket_client', $clientDef);

        $alias = new Alias('gos_web_socket.websocket_client_factory');

        if ($usesSymfony51Api) {
            $alias->setDeprecated(
                'gos/web-socket-bundle',
                '3.4',
                'The "%alias_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        } else {
            $alias->setDeprecated(
                true,
                'The "%alias_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the ratchet/pawl package instead.'
            );
        }

        foreach ([Client::class, ClientInterface::class] as $aliasedObject) {
            $container->setAlias($aliasedObject, $alias);
        }
    }

    /**
     * @throws RuntimeException if required dependencies are missing
     */
    public function prepend(ContainerBuilder $container): void
    {
        /** @var array<string, class-string> $bundles */
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['GosPubSubRouterBundle'])) {
            throw new RuntimeException('The GosWebSocketBundle requires the GosPubSubRouterBundle.');
        }

        // Prepend the websocket router now so the pubsub bundle creates the router service, we will inject the resources into the service with a compiler pass
        $container->prependExtensionConfig(
            'gos_pubsub_router',
            [
                'routers' => [
                    'websocket' => [],
                ],
            ]
        );
    }
}
