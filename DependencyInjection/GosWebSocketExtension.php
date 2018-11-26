<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Periodic\PeriodicInterface;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class GosWebSocketExtension extends Extension implements PrependExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config/services')
        );

        $loader->load('services.yml');

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

            $pubsubConfig = $configs['server']['router'] ?? [];

            // The router was configured through the prepend pass, we only need to change the router the WampRouter uses
            if (!empty($pubsubConfig)) {
                $container->getDefinition('gos_web_socket.router.wamp')
                    ->replaceArgument(0, new Reference('gos_pubsub_router.websocket'));
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

                $container->getDefinition('gos_web_socket.client_storage')
                    ->addMethodCall('setStorageDriver', [new Reference($storageDriver)]);
            }
        }

        if (!empty($configs['rpc'])) {
            @trigger_error(
                'Configuring RPC handlers with the `gos_web_socket.rpc` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.rpc` tag to your service definitions instead.',
                E_USER_DEPRECATED
            );

            $def = $container->getDefinition('gos_web_socket.rpc.registry');

            foreach ($configs['rpc'] as $rpc) {
                $def->addMethodCall('addRpc', [new Reference(ltrim($rpc, '@'))]);
            }
        }

        if (!empty($configs['topics'])) {
            @trigger_error(
                'Configuring topic handlers with the `gos_web_socket.topics` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.topic` tag to your service definitions instead.',
                E_USER_DEPRECATED
            );

            $def = $container->getDefinition('gos_web_socket.topic.registry');

            foreach ($configs['topics'] as $topic) {
                $def->addMethodCall('addTopic', [new Reference(ltrim($topic, '@'))]);
            }
        }

        if (!empty($configs['periodic'])) {
            @trigger_error(
                'Configuring periodic handlers with the `gos_web_socket.periodic` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.periodic` tag to your service definitions instead.',
                E_USER_DEPRECATED
            );

            $def = $container->getDefinition('gos_web_socket.periodic.registry');

            foreach ($configs['periodic'] as $periodic) {
                $def->addMethodCall('addPeriodic', [new Reference(ltrim($periodic, '@'))]);
            }
        }

        if (!empty($configs['servers'])) {
            @trigger_error(
                'Configuring servers with the `gos_web_socket.servers` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.server` tag to your service definitions instead.',
                E_USER_DEPRECATED
            );

            $def = $container->getDefinition('gos_web_socket.server.registry');

            foreach ($configs['servers'] as $server) {
                $def->addMethodCall('addServer', [new Reference(ltrim($server, '@'))]);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['GosPubSubRouterBundle'])) {
            throw new \RuntimeException('The GosWebSocketBundle requires the GosPubSubRouterBundle.');
        }

        $config = $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));

        // GosPubSubRouterBundle
        if (isset($config['server'])) {
            $pubsubConfig = $config['server']['router'] ?? [];

            if (!empty($pubsubConfig)) {
                if (!isset($pubsubConfig['context']['tokenSeparator'])) {
                    $pubsubConfig['context']['tokenSeparator'] = Configuration::DEFAULT_TOKEN_SEPARATOR;
                }

                $container->prependExtensionConfig(
                    'gos_pubsub_router',
                    [
                        'routers' => [
                            'websocket' => $pubsubConfig,
                        ],
                    ]
                );
            }
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
