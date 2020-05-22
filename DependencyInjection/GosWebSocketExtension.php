<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Client\ClientManipulatorInterface;
use Gos\Bundle\WebSocketBundle\Client\WebSocketUserTrait;
use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
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
        $loader->load('aliases.yml');

        $configuration = new Configuration();
        $configs = $this->processConfiguration($configuration, $configs);

        // Mark services deprecated if the API supports it
        if (method_exists(Definition::class, 'setDeprecated')) {
            $container->getDefinition('gos_web_socket.server_command')
                ->setDeprecated(true, 'The "%service_id%" service is deprecated. Use the "gos_web_socket.websocket_server.command" service instead.');

            $container->getDefinition('gos_web_socket.twig.extension')
                ->setDeprecated(true, 'The "%service_id%" service is deprecated. Support for Assetic will be removed.');

            $container->getDefinition('gos_web_socket.zmq.pusher')
                ->setDeprecated(true, 'The "%service_id%" service is deprecated. Support for ZMQ as a pusher will be removed.');

            $container->getDefinition('gos_web_socket.zmq.server_push_handler')
                ->setDeprecated(true, 'The "%service_id%" service is deprecated. Support for ZMQ as a server push handler will be removed.');
        }

        $container->setParameter(
            'web_socket_server.port',
            $configs['server']['port']
        );

        $container->setParameter(
            'web_socket_server.host',
            $configs['server']['host']
        );

        $originsRegistryDef = $container->getDefinition('gos_web_socket.origins.registry');
        $container->setParameter('web_socket_origin_check', $configs['server']['origin_check']);

        if (!empty($configs['origins'])) {
            foreach ($configs['origins'] as $origin) {
                $originsRegistryDef->addMethodCall('addOrigin', [
                    $origin,
                ]);
            }
        }

        $container->setParameter('web_socket_server.client_storage.ttl', $configs['client']['storage']['ttl']);
        $container->setParameter('web_socket_server.client_storage.prefix', $configs['client']['storage']['prefix']);

        //client
        if (isset($configs['client'])) {
            $clientConf = $configs['client'];
            $container->setParameter('gos_web_socket.firewall', (array) $clientConf['firewall']);

            if (isset($clientConf['session_handler'])) {
                $def = $container->getDefinition('gos_web_socket.ws.server');
                $def->addMethodCall('setSessionHandler', [
                    new Reference(ltrim($clientConf['session_handler'], '@')),
                ]);

                $container->setAlias('gos_web_socket.session_handler', ltrim($clientConf['session_handler'], '@'));
            }

            if (isset($clientConf['storage']['driver'])) {
                $driverRef = ltrim($clientConf['storage']['driver'], '@');
                $clientStorageDef = $container->getDefinition('gos_web_socket.client_storage');

                if (isset($clientConf['storage']['decorator'])) {
                    $decoratorRef = ltrim($clientConf['storage']['decorator'], '@');
                    $decoratorDef = $container->getDefinition($decoratorRef);
                    $decoratorDef->addArgument(new Reference($driverRef));

                    $clientStorageDef->addMethodCall('setStorageDriver', [
                        new Reference($decoratorRef),
                    ]);
                } else {
                    $clientStorageDef->addMethodCall('setStorageDriver', [
                        new Reference($driverRef),
                    ]);
                }
            }
        }

        //rpc
        if (!empty($configs['rpc'])) {
            trigger_deprecation('gos/web-socket-bundle', '1.9', 'Configuring RPC handlers with the `gos_web_socket.rpc` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.rpc` tag to your service definitions instead.');

            $def = $container->getDefinition('gos_web_socket.rpc.registry');

            foreach ($configs['rpc'] as $rpc) {
                $def->addMethodCall('addRpc', [
                    new Reference(ltrim($rpc, '@')),
                ]);
            }
        }

        //topic
        if (!empty($configs['topics'])) {
            trigger_deprecation('gos/web-socket-bundle', '1.9', 'Configuring topic handlers with the `gos_web_socket.topics` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.topic` tag to your service definitions instead.');

            $def = $container->getDefinition('gos_web_socket.topic.registry');

            foreach ($configs['topics'] as $topic) {
                $def->addMethodCall('addTopic', [
                    new Reference(ltrim($topic, '@')),
                ]);
            }
        }

        //periodic
        if (!empty($configs['periodic'])) {
            trigger_deprecation('gos/web-socket-bundle', '1.9', 'Configuring periodic handlers with the `gos_web_socket.periodic` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.periodic` tag to your service definitions instead.');

            $def = $container->getDefinition('gos_web_socket.periodic.registry');

            foreach ($configs['periodic'] as $periodic) {
                $def->addMethodCall('addPeriodic', [
                    new Reference(ltrim($periodic, '@')),
                ]);
            }
        }

        //server
        if (!empty($configs['servers'])) {
            trigger_deprecation('gos/web-socket-bundle', '1.9', 'Configuring servers with the `gos_web_socket.servers` config node is deprecated and will be removed in 2.0. Add the `gos_web_socket.server` tag to your service definitions instead.');

            $def = $container->getDefinition('gos_web_socket.server.registry');

            foreach ($configs['servers'] as $server) {
                $def->addMethodCall('addServer', [
                    new Reference(ltrim($server, '@')),
                ]);
            }
        }

        //PubSub router
        $pubsubConfig = isset($configs['server']['router'])
            ? $configs['server']['router']
            : [];

        if (!empty($pubsubConfig)) {
            $container->getDefinition('gos_web_socket.router.wamp')
                ->replaceArgument(0, new Reference('gos_pubsub_router.websocket'));
        }

        // Pusher Configuration
        if (isset($configs['pushers'])) {
            // Validate WAMP configuration
            if (isset($configs['pushers']['wamp'])) {
                if (!is_bool($configs['pushers']['wamp']['ssl'])) {
                    throw new \InvalidArgumentException(
                        sprintf('The ssl node under wamp pusher configuration must be a boolean value')
                    );
                }
            }

            // Deprecate ZMQ pusher
            if (isset($configs['pushers']['zmq'])) {
                trigger_deprecation('gos/web-socket-bundle', '1.10', 'Support for the ZMQ pusher is deprecated and will be removed in 2.0.');
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

        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (!isset($config['server'])) {
            $config['server'] = array();
        }

        //PubSubRouter
        $pubsubConfig = isset($config['server']['router']) ? $config['server']['router'] : [];

        if (!empty($pubsubConfig)) {
            if (!isset($pubsubConfig['context']['tokenSeparator'])) {
                $pubsubConfig['context']['tokenSeparator'] = Configuration::DEFAULT_TOKEN_SEPARATOR;
            }

            $container->prependExtensionConfig('gos_pubsub_router', [
                    'routers' => [
                        'websocket' => $pubsubConfig,
                    ],
                ]
            );
        }

        //assetic
        if (isset($bundles['AsseticBundle']) && true === $config['assetic']) {
            $asseticConfig = array(
                'bundles' => array('GosWebSocketBundle'),
            );

            $container->prependExtensionConfig('assetic', $asseticConfig);
        }

        //twig
        if (isset($config['shared_config']) && true === $config['shared_config']) {
            if (!isset($bundles['TwigBundle'])) {
                throw new \RuntimeException('Shared configuration required Twig Bundle');
            }

            $twigConfig = array('globals' => array());

            if (isset($config['server']['host'])) {
                $twigConfig['globals']['gos_web_socket_server_host'] = $config['server']['host'];
            }

            if (isset($config['server']['port'])) {
                $twigConfig['globals']['gos_web_socket_server_port'] = $config['server']['port'];
            }

            if (!empty($twigConfig['globals'])) {
                $container->prependExtensionConfig('twig', $twigConfig);
            }

            $container->prependExtensionConfig('twig', $twigConfig);
        }

        //monolog
        if (isset($bundles['MonologBundle'])) {
            $monologConfig = array(
                'channels' => array('websocket'),
                'handlers' => array(
                    'websocket' => array(
                        'type' => 'console',
                        'verbosity_levels' => array(
                            'VERBOSITY_NORMAL' => true === $container->getParameter('kernel.debug') ? Logger::DEBUG : Logger::INFO,
                        ),
                        'channels' => array(
                            'type' => 'inclusive',
                            'elements' => array('websocket'),
                        ),
                    ),
                ),
            );

            $container->prependExtensionConfig('monolog', $monologConfig);
        }
    }
}
