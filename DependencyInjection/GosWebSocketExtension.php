<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactory;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactory;
use Gos\Bundle\WebSocketBundle\Pusher\Zmq\ZmqConnectionFactory;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class GosWebSocketExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Map containing a list of deprecated service keys where the key is the deprecated alias and the value is the new service identifier.
     */
    private const DEPRECATED_SERVICE_ALIASES = [
        'gos_web_socket.amqp.pusher' => 'gos_web_socket.pusher.amqp',
        'gos_web_socket.amqp.server_push_handler' => 'gos_web_socket.pusher.amqp.push_handler',
        'gos_web_socket.client_event.listener' => 'gos_web_socket.event_listener.client',
        'gos_web_socket.client_storage' => 'gos_web_socket.client.storage',
        'gos_web_socket.client_storage.doctrine.decorator' => 'gos_web_socket.client.driver.doctrine_cache',
        'gos_web_socket.client_storage.symfony.decorator' => 'gos_web_socket.client.driver.symfony_cache',
        'gos_web_socket.data_collector' => 'gos_web_socket.data_collector.websocket',
        'gos_web_socket.entry_point' => 'gos_web_socket.server.entry_point',
        'gos_web_socket.kernel_event.terminate' => 'gos_web_socket.event_listener.kernel_terminate',
        'gos_web_socket.memory_usage.periodic' => 'gos_web_socket.periodic_ping.memory_usage',
        'gos_web_socket.origins.registry' => 'gos_web_socket.registry.origins',
        'gos_web_socket.pnctl_event.listener' => 'gos_web_socket.event_listener.start_server',
        'gos_web_socket.periodic.registry' => 'gos_web_socket.registry.periodic',
        'gos_web_socket.pusher_registry' => 'gos_web_socket.registry.pusher',
        'gos_web_socket.rpc.dispatcher' => 'gos_web_socket.dispatcher.rpc',
        'gos_web_socket.rpc.registry' => 'gos_web_socket.registry.rpc',
        'gos_web_socket.server.in_memory.client_storage.driver' => 'gos_web_socket.client.driver.in_memory',
        'gos_web_socket.server.registry' => 'gos_web_socket.registry.server',
        'gos_web_socket.server_push_handler.registry' => 'gos_web_socket.registry.server_push_handler',
        'gos_web_socket.topic.dispatcher' => 'gos_web_socket.dispatcher.topic',
        'gos_web_socket.topic.registry' => 'gos_web_socket.registry.topic',
        'gos_web_socket.wamp.pusher' => 'gos_web_socket.pusher.wamp',
        'gos_web_socket.websocket_server.command' => 'gos_web_socket.command.websocket_server',
        'gos_web_socket.ws.server' => 'gos_web_socket.server.websocket',
        'gos_web_socket.ws.server_builder' => 'gos_web_socket.server.builder',
        'gos_web_socket.websocket_authentification.provider' => 'gos_web_socket.client.authentication.websocket_provider',
        'gos_web_socket.websocket.client_manipulator' => 'gos_web_socket.client.manipulator',
        'gos_web_socket.zmq.pusher' => 'gos_web_socket.pusher.zmq',
        'gos_web_socket.zmq.server_push_handler' => 'gos_web_socket.pusher.zmq.push_handler',
        'gos_web_socket_server.wamp_application' => 'gos_web_socket.server.application.wamp',
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config/services')
        );

        $loader->load('services.yml');
        $loader->load('aliases.yml');
        $loader->load('deprecated_aliases.yml');

        $configs = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $container->setParameter('gos_web_socket.client.storage.ttl', $configs['client']['storage']['ttl']);
        $container->setParameter('gos_web_socket.client.storage.prefix', $configs['client']['storage']['prefix']);

        if (isset($configs['server'])) {
            if (isset($configs['server']['port'])) {
                $container->setParameter('gos_web_socket.server.port', $container->resolveEnvPlaceholders($configs['server']['port']));
            }

            if (isset($configs['server']['host'])) {
                $container->setParameter('gos_web_socket.server.host', $container->resolveEnvPlaceholders($configs['server']['host']));
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

            // Register Twig globals if Twig is available and shared_config is set
            if ($configs['shared_config'] && $container->hasDefinition('twig')) {
                $twigDef = $container->getDefinition('twig');

                if ($container->hasParameter('gos_web_socket.server.host')) {
                    $twigDef->addMethodCall(
                        'addGlobal',
                        [
                            'gos_web_socket_server_host',
                            new Parameter('gos_web_socket.server.host'),
                        ]
                    );
                }

                if ($container->hasParameter('gos_web_socket.server.port')) {
                    $twigDef->addMethodCall(
                        'addGlobal',
                        [
                            'gos_web_socket_server_port',
                            new Parameter('gos_web_socket.server.port'),
                        ]
                    );
                }
            }
        }

        if (!empty($configs['origins'])) {
            $originsRegistryDef = $container->getDefinition('gos_web_socket.registry.origins');

            foreach ($configs['origins'] as $origin) {
                $originsRegistryDef->addMethodCall('addOrigin', [$origin]);
            }
        }

        if (isset($configs['client'])) {
            $clientConf = $configs['client'];
            $container->setParameter('gos_web_socket.firewall', (array) $clientConf['firewall']);

            if (isset($clientConf['session_handler'])) {
                $sessionHandler = ltrim($clientConf['session_handler'], '@');

                $container->getDefinition('gos_web_socket.server.builder')
                    ->addMethodCall('setSessionHandler', [new Reference($sessionHandler)]);

                $container->setAlias('gos_web_socket.session_handler', $sessionHandler);
            }

            if (isset($clientConf['storage']['driver'])) {
                $driverRef = ltrim($clientConf['storage']['driver'], '@');
                $storageDriver = $driverRef;

                if (isset($clientConf['storage']['decorator'])) {
                    $decoratorRef = ltrim($clientConf['storage']['decorator'], '@');
                    $container->getDefinition($decoratorRef)
                        ->addArgument(new Reference($driverRef));

                    $storageDriver = $decoratorRef;
                }

                // Alias the DriverInterface in use
                $container->setAlias(DriverInterface::class, new Alias($storageDriver));

                $container->getDefinition('gos_web_socket.client.storage')
                    ->addMethodCall('setStorageDriver', [new Reference($storageDriver)]);
            }
        }

        $this->loadPingServices($configs, $container);
        $this->loadPushers($configs, $container);

        // Mark service aliases deprecated if able
        if (method_exists(Alias::class, 'setDeprecated')) {
            foreach (self::DEPRECATED_SERVICE_ALIASES as $deprecatedAlias => $newService) {
                if ($container->hasAlias($deprecatedAlias)) {
                    $container->getAlias($deprecatedAlias)
                        ->setDeprecated(
                            true,
                            'The "%alias_id%" service alias is deprecated and will be removed in GosWebSocketBundle 3.0, you should use the "'.$newService.'" service instead.'
                        );
                }
            }
        }
    }

    /**
     * @throws InvalidArgumentException if an unsupported ping service type is given
     */
    private function loadPingServices(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['ping'])) {
            return;
        }

        if (isset($configs['ping']['services'])) {
            foreach ($configs['ping']['services'] as $pingService) {
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
                        throw new InvalidArgumentException(
                            sprintf('Unsupported ping service type "%s"', $pingService['type'])
                        );
                }
            }
        }
    }

    private function loadPushers(array $configs, ContainerBuilder $container): void
    {
        if (!isset($configs['pushers'])) {
            // Untag all of the pushers
            foreach (['gos_web_socket.pusher.amqp', 'gos_web_socket.pusher.wamp', 'gos_web_socket.pusher.zmq'] as $pusher) {
                $container->getDefinition($pusher)
                    ->clearTag('gos_web_socket.pusher');
            }

            foreach (['gos_web_socket.pusher.amqp.push_handler', 'gos_web_socket.pusher.zmq.push_handler'] as $pusher) {
                $container->getDefinition($pusher)
                    ->clearTag('gos_web_socket.push_handler');
            }

            return;
        }

        if (isset($configs['pushers']['amqp']) && $configs['pushers']['amqp']['enabled']) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['amqp'];
            unset($factoryConfig['enabled']);

            // Resolve placeholders for host and port
            $factoryConfig['host'] = $container->resolveEnvPlaceholders($factoryConfig['host']);
            $factoryConfig['port'] = $container->resolveEnvPlaceholders($factoryConfig['port']);

            $connectionFactoryDef = new Definition(
                AmqpConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPrivate(true);

            $container->setDefinition('gos_web_socket.pusher.amqp.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.pusher.amqp')
                ->setArgument(2, new Reference('gos_web_socket.pusher.amqp.connection_factory'));

            $container->getDefinition('gos_web_socket.pusher.amqp.push_handler')
                ->setArgument(3, new Reference('gos_web_socket.pusher.amqp.connection_factory'));
        } else {
            $container->getDefinition('gos_web_socket.pusher.amqp')
                ->clearTag('gos_web_socket.pusher');

            $container->getDefinition('gos_web_socket.pusher.amqp.push_handler')
                ->clearTag('gos_web_socket.push_handler');
        }

        if (isset($configs['pushers']['zmq']) && $configs['pushers']['zmq']['enabled']) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['zmq'];
            unset($factoryConfig['enabled']);

            // Resolve placeholders for host and port
            $factoryConfig['host'] = $container->resolveEnvPlaceholders($factoryConfig['host']);
            $factoryConfig['port'] = $container->resolveEnvPlaceholders($factoryConfig['port']);

            $connectionFactoryDef = new Definition(
                ZmqConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPrivate(true);

            $container->setDefinition('gos_web_socket.pusher.zmq.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.pusher.zmq')
                ->setArgument(2, new Reference('gos_web_socket.pusher.zmq.connection_factory'));

            $container->getDefinition('gos_web_socket.pusher.zmq.push_handler')
                ->setArgument(4, new Reference('gos_web_socket.pusher.zmq.connection_factory'));
        } else {
            $container->getDefinition('gos_web_socket.pusher.zmq')
                ->clearTag('gos_web_socket.pusher');

            $container->getDefinition('gos_web_socket.pusher.zmq.push_handler')
                ->clearTag('gos_web_socket.push_handler');
        }

        if (isset($configs['pushers']['wamp']) && $configs['pushers']['wamp']['enabled']) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['wamp'];
            unset($factoryConfig['enabled']);

            // Resolve placeholders for host and port
            $factoryConfig['host'] = $container->resolveEnvPlaceholders($factoryConfig['host']);
            $factoryConfig['port'] = $container->resolveEnvPlaceholders($factoryConfig['port']);

            $connectionFactoryDef = new Definition(
                WampConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPrivate(true);
            $connectionFactoryDef->addMethodCall('setLogger', [new Reference('monolog.logger.websocket', ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);

            $container->setDefinition('gos_web_socket.pusher.wamp.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.pusher.wamp')
                ->setArgument(2, new Reference('gos_web_socket.pusher.wamp.connection_factory'));
        } else {
            $container->getDefinition('gos_web_socket.pusher.wamp')
                ->clearTag('gos_web_socket.pusher');
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

        $config = $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));

        // GosPubSubRouterBundle
        if (isset($config['server'])) {
            $pubsubConfig = $config['server']['router'] ?? [];

            $container->prependExtensionConfig(
                'gos_pubsub_router',
                [
                    'routers' => [
                        'websocket' => $pubsubConfig,
                    ],
                ]
            );
        }

        // MonologBundle
        if (isset($bundles['MonologBundle'])) {
            $monologConfig = [
                'channels' => ['websocket'],
                'handlers' => [
                    'websocket' => [
                        'type' => 'console',
                        'verbosity_levels' => [
                            'VERBOSITY_NORMAL' => $container->getParameter('kernel.debug') ? Logger::DEBUG : Logger::INFO,
                        ],
                        'channels' => [
                            'type' => 'inclusive',
                            'elements' => ['websocket'],
                        ],
                    ],
                ],
            ];

            $container->prependExtensionConfig('monolog', $monologConfig);
        }
    }
}
