<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
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
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class GosWebSocketExtension extends Extension implements PrependExtensionInterface
{
    private const DEPRECATED_ALIASES = [
        PusherRegistry::class => '3.1',
        ServerPushHandlerRegistry::class => '3.1',
    ];

    private const DEPRECATED_SERVICES = [
        'gos_web_socket.client.driver.doctrine_cache' => '3.4',
        'gos_web_socket.data_collector.websocket' => '3.1',
        'gos_web_socket.event_listener.close_pusher_connections' => '3.1',
        'gos_web_socket.event_listener.register_push_handlers' => '3.1',
        'gos_web_socket.pusher.amqp' => '3.1',
        'gos_web_socket.pusher.amqp.push_handler' => '3.1',
        'gos_web_socket.pusher.wamp' => '3.1',
        'gos_web_socket.registry.pusher' => '3.1',
        'gos_web_socket.registry.server_push_handler' => '3.1',
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));

        $loader->load('services.yaml');
        $loader->load('aliases.yaml');

        $configs = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $container->setParameter('gos_web_socket.shared_config', $configs['shared_config']);

        $this->registerClientConfiguration($configs, $container);
        $this->registerServerConfiguration($configs, $container);
        $this->registerOriginsConfiguration($configs, $container);
        $this->registerPingConfiguration($configs, $container);
        $this->registerPushersConfiguration($configs, $container);
        $this->registerWebsocketClientConfiguration($configs, $container);

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

    private function registerClientConfiguration(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['client'])) {
            return;
        }

        $container->setParameter('gos_web_socket.client.storage.ttl', $configs['client']['storage']['ttl']);
        $container->setParameter('gos_web_socket.firewall', (array) $configs['client']['firewall']);

        // @deprecated to be removed in 4.0, parameter is unused
        $container->setParameter('gos_web_socket.client.storage.prefix', $configs['client']['storage']['prefix']);

        if (isset($configs['client']['session_handler'])) {
            $sessionHandler = ltrim($configs['client']['session_handler'], '@');

            $container->getDefinition('gos_web_socket.server.builder')
                ->addMethodCall('setSessionHandler', [new Reference($sessionHandler)]);

            $container->setAlias('gos_web_socket.session_handler', $sessionHandler);
        }

        if (isset($configs['client']['storage']['driver'])) {
            $driverRef = ltrim($configs['client']['storage']['driver'], '@');
            $storageDriver = $driverRef;

            if (isset($configs['client']['storage']['decorator'])) {
                $decoratorRef = ltrim($configs['client']['storage']['decorator'], '@');
                $container->getDefinition($decoratorRef)
                    ->addArgument(new Reference($driverRef));

                $storageDriver = $decoratorRef;
            }

            // Alias the DriverInterface in use for autowiring
            $container->setAlias(DriverInterface::class, new Alias($storageDriver));

            $container->getDefinition('gos_web_socket.client.storage')
                ->replaceArgument(0, new Reference($storageDriver));
        }
    }

    private function registerServerConfiguration(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['server'])) {
            return;
        }

        if (isset($configs['server']['port'])) {
            $container->setParameter('gos_web_socket.server.port', $configs['server']['port']);
        }

        if (isset($configs['server']['host'])) {
            $container->setParameter('gos_web_socket.server.host', $configs['server']['host']);
        }

        if (isset($configs['server']['origin_check'])) {
            $container->setParameter('gos_web_socket.server.origin_check', $configs['server']['origin_check']);
        }

        if (isset($configs['server']['keepalive_ping'])) {
            $container->setParameter('gos_web_socket.server.keepalive_ping', $configs['server']['keepalive_ping']);
        }

        if (isset($configs['server']['keepalive_interval'])) {
            $container->setParameter('gos_web_socket.server.keepalive_interval', $configs['server']['keepalive_interval']);
        }

        if (isset($configs['server']['router'])) {
            $routerConfig = [];

            // Adapt configuration based on the version of GosPubSubRouterBundle installed, if the XML loader is available the newer configuration structure is used
            if (isset($configs['server']['router']['resources'])) {
                foreach ($configs['server']['router']['resources'] as $resource) {
                    if (is_array($resource)) {
                        $routerConfig[] = $resource;
                    } else {
                        $routerConfig[] = [
                            'resource' => $resource,
                            'type' => null,
                        ];
                    }
                }
            }

            $container->setParameter('gos_web_socket.router_resources', $routerConfig);
        }
    }

    private function registerOriginsConfiguration(array $configs, ContainerBuilder $container): void
    {
        $originsRegistryDef = $container->getDefinition('gos_web_socket.registry.origins');

        foreach ($configs['origins'] as $origin) {
            $originsRegistryDef->addMethodCall('addOrigin', [$origin]);
        }
    }

    /**
     * @throws InvalidArgumentException if an unsupported ping service type is given
     */
    private function registerPingConfiguration(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['ping'])) {
            return;
        }

        foreach ((array) $configs['ping']['services'] as $pingService) {
            switch ($pingService['type']) {
                case Configuration::PING_SERVICE_TYPE_DOCTRINE:
                    $serviceRef = ltrim($pingService['name'], '@');

                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.doctrine');
                    $definition->addArgument(new Reference($serviceRef));
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.doctrine.'.$serviceRef, $definition);

                    break;

                case Configuration::PING_SERVICE_TYPE_PDO:
                    $serviceRef = ltrim($pingService['name'], '@');

                    $definition = new ChildDefinition('gos_web_socket.periodic_ping.pdo');
                    $definition->addArgument(new Reference($serviceRef));
                    $definition->addTag('gos_web_socket.periodic');

                    $container->setDefinition('gos_web_socket.periodic_ping.pdo.'.$serviceRef, $definition);

                    break;

                default:
                    throw new InvalidArgumentException(sprintf('Unsupported ping service type "%s"', $pingService['type']));
            }
        }
    }

    private function registerPushersConfiguration(array $configs, ContainerBuilder $container): void
    {
        $usesSymfony51Api = method_exists(Definition::class, 'getDeprecation');

        if (!isset($configs['pushers'])) {
            // Remove all of the pushers
            foreach (['gos_web_socket.pusher.amqp', 'gos_web_socket.pusher.wamp'] as $pusher) {
                $container->removeDefinition($pusher);
            }

            foreach (['gos_web_socket.pusher.amqp.push_handler'] as $pusher) {
                $container->removeDefinition($pusher);
            }

            return;
        }

        if (isset($configs['pushers']['amqp']) && $this->isConfigEnabled($container, $configs['pushers']['amqp'])) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['amqp'];
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

        if (isset($configs['pushers']['wamp']) && $this->isConfigEnabled($container, $configs['pushers']['wamp'])) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['wamp'];
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

    private function registerWebsocketClientConfiguration(array $configs, ContainerBuilder $container): void
    {
        $usesSymfony51Api = method_exists(Definition::class, 'getDeprecation');

        if (!isset($configs['websocket_client']) || !$configs['websocket_client']['enabled']) {
            return;
        }

        // Pull the 'enabled' field out of the client's config
        $factoryConfig = $configs['websocket_client'];
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

        foreach ([ClientFactory::class, ClientFactoryInterface::class] as $aliasedObject) {
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

        foreach ([Client::class, ClientInterface::class] as $aliasedObject) {
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

            $container->setAlias($aliasedObject, $alias);
        }
    }

    /**
     * @throws RuntimeException if required dependencies are missing
     */
    public function prepend(ContainerBuilder $container): void
    {
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
