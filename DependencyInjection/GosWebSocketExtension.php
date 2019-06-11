<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Client\Driver\DriverInterface;
use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactory;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactory;
use Gos\Bundle\WebSocketBundle\Pusher\Zmq\ZmqConnectionFactory;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Component\WebSocketClient\Wamp\Client;
use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class GosWebSocketExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config/services')
        );

        $loader->load('services.yml');
        $loader->load('aliases.yml');

        $configs = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $container->registerForAutoconfiguration(PeriodicInterface::class)->addTag('gos_web_socket.periodic');
        $container->registerForAutoconfiguration(RpcInterface::class)->addTag('gos_web_socket.rpc');
        $container->registerForAutoconfiguration(ServerInterface::class)->addTag('gos_web_socket.server');
        $container->registerForAutoconfiguration(TopicInterface::class)->addTag('gos_web_socket.topic');

        $container->setParameter('web_socket_server.client_storage.ttl', $configs['client']['storage']['ttl']);
        $container->setParameter('web_socket_server.client_storage.prefix', $configs['client']['storage']['prefix']);

        if (isset($configs['server'])) {
            if (isset($configs['server']['port'])) {
                $container->setParameter('web_socket_server.port', $configs['server']['port']);
            }

            if (isset($configs['server']['host'])) {
                $container->setParameter('web_socket_server.host', $configs['server']['host']);
            }

            if (isset($configs['server']['origin_check'])) {
                $container->setParameter('web_socket_origin_check', $configs['server']['origin_check']);
            }

            if (isset($configs['server']['keepalive_ping'])) {
                $container->setParameter('web_socket_keepalive_ping', $configs['server']['keepalive_ping']);
            }

            if (isset($configs['server']['keepalive_interval'])) {
                $container->setParameter('web_socket_keepalive_interval', $configs['server']['keepalive_interval']);
            }
        }

        if (!empty($configs['origins'])) {
            $originsRegistryDef = $container->getDefinition('gos_web_socket.origins.registry');

            foreach ($configs['origins'] as $origin) {
                $originsRegistryDef->addMethodCall('addOrigin', [$origin]);
            }
        }

        if (isset($configs['client'])) {
            $clientConf = $configs['client'];
            $container->setParameter('gos_web_socket.firewall', (array) $clientConf['firewall']);

            if (isset($clientConf['session_handler'])) {
                $sessionHandler = ltrim($clientConf['session_handler'], '@');

                $container->getDefinition('gos_web_socket.ws.server_builder')
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

                $container->getDefinition('gos_web_socket.client_storage')
                    ->addMethodCall('setStorageDriver', [new Reference($storageDriver)]);
            }
        }

        $this->loadPingServices($configs, $container);
        $this->loadPushers($configs, $container);
    }

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
            foreach (['gos_web_socket.amqp.pusher', 'gos_web_socket.wamp.pusher', 'gos_web_socket.zmq.pusher'] as $pusher) {
                $container->getDefinition($pusher)
                    ->clearTag('gos_web_socket.pusher');
            }

            foreach (['gos_web_socket.amqp.server_push_handler', 'gos_web_socket.zmq.server_push_handler'] as $pusher) {
                $container->getDefinition($pusher)
                    ->clearTag('gos_web_socket.push_handler');
            }

            return;
        }

        if (isset($configs['pushers']['amqp']) && $configs['pushers']['amqp']['enabled']) {
            if (!extension_loaded('amqp')) {
                throw new RuntimeException('The AMQP pusher requires the PHP amqp extension.');
            }

            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['amqp'];
            unset($factoryConfig['enabled']);

            $connectionFactoryDef = new Definition(
                AmqpConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPrivate(true);

            $container->setDefinition('gos_web_socket.amqp.pusher.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.amqp.pusher')
                ->setArgument(0, new Reference('gos_web_socket.amqp.pusher.connection_factory'));

            $container->getDefinition('gos_web_socket.amqp.server_push_handler')
                ->setArgument(4, new Reference('gos_web_socket.amqp.pusher.connection_factory'));
        } else {
            $container->getDefinition('gos_web_socket.amqp.pusher')
                ->clearTag('gos_web_socket.pusher');

            $container->getDefinition('gos_web_socket.amqp.server_push_handler')
                ->clearTag('gos_web_socket.push_handler');
        }

        if (isset($configs['pushers']['zmq']) && $configs['pushers']['zmq']['enabled']) {
            if (!extension_loaded('zmq')) {
                throw new RuntimeException('The ZMQ pusher requires the PHP zmq extension.');
            }

            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['zmq'];
            unset($factoryConfig['enabled']);

            $connectionFactoryDef = new Definition(
                ZmqConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPrivate(true);

            $container->setDefinition('gos_web_socket.zmq.pusher.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.zmq.pusher')
                ->setArgument(0, new Reference('gos_web_socket.zmq.pusher.connection_factory'));

            $container->getDefinition('gos_web_socket.zmq.server_push_handler')
                ->setArgument(4, new Reference('gos_web_socket.zmq.pusher.connection_factory'));
        } else {
            $container->getDefinition('gos_web_socket.zmq.pusher')
                ->clearTag('gos_web_socket.pusher');

            $container->getDefinition('gos_web_socket.zmq.server_push_handler')
                ->clearTag('gos_web_socket.push_handler');
        }

        if (isset($configs['pushers']['wamp']) && $configs['pushers']['wamp']['enabled']) {
            // Pull the 'enabled' field out of the pusher's config
            $factoryConfig = $configs['pushers']['wamp'];
            unset($factoryConfig['enabled']);

            $connectionFactoryDef = new Definition(
                WampConnectionFactory::class,
                [
                    $factoryConfig,
                ]
            );
            $connectionFactoryDef->setPrivate(true);

            $container->setDefinition('gos_web_socket.wamp.pusher.connection_factory', $connectionFactoryDef);

            $container->getDefinition('gos_web_socket.wamp.pusher')
                ->setArgument(0, new Reference('gos_web_socket.wamp.pusher.connection_factory'));
        } else {
            $container->getDefinition('gos_web_socket.wamp.pusher')
                ->clearTag('gos_web_socket.pusher');
        }
    }

    /**
     * @throws RuntimeException
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

        // TwigBundle
        if (isset($bundles['TwigBundle'])) {
            if (isset($config['shared_config'], $config['server']) && $config['shared_config']) {
                $twigConfig = ['globals' => []];

                if (isset($config['server']['host'])) {
                    $twigConfig['globals']['gos_web_socket_server_host'] = $config['server']['host'];
                }

                if (isset($config['server']['port'])) {
                    $twigConfig['globals']['gos_web_socket_server_port'] = $config['server']['port'];
                }

                if (!empty($twigConfig['globals'])) {
                    $container->prependExtensionConfig('twig', $twigConfig);
                }
            }
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
