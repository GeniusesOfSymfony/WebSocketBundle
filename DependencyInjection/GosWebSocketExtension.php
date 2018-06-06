<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Gos\Bundle\WebSocketBundle\Client\ClientStorage;
use Gos\Bundle\WebSocketBundle\Router\WampRouter;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\OriginRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\WebSocketServer;
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

        $configuration = new Configuration();
        $configs = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'web_socket_server.port',
            $configs['server']['port']
        );

        $container->setParameter(
            'web_socket_server.host',
            $configs['server']['host']
        );

        $originsRegistryDef = $container->getDefinition(OriginRegistry::class);
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
            $container->setParameter('gos_web_socket.firewall', (array)$clientConf['firewall']);

            if (isset($clientConf['session_handler'])) {
                $def = $container->getDefinition(WebSocketServer::class);
                $def->addMethodCall('setSessionHandler', [
                    new Reference(ltrim($clientConf['session_handler'], '@')),
                ]);

                $container->setAlias('gos_web_socket.session_handler', ltrim($clientConf['session_handler'], '@'));
            }

            if (isset($clientConf['storage']['driver'])) {
                $driverRef = ltrim($clientConf['storage']['driver'], '@');
                $clientStorageDef = $container->getDefinition(ClientStorage::class);

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
            $def = $container->getDefinition(RpcRegistry::class);

            foreach ($configs['rpc'] as $rpc) {
                $def->addMethodCall('addRpc', [
                    new Reference(ltrim($rpc, '@')),
                ]);
            }
        }

        //topic
        if (!empty($configs['topics'])) {
            $def = $container->getDefinition(TopicRegistry::class);

            foreach ($configs['topics'] as $topic) {
                $def->addMethodCall('addTopic', [
                    new Reference(ltrim($topic, '@')),
                ]);
            }
        }

        //periodic
        if (!empty($configs['periodic'])) {
            $def = $container->getDefinition(PeriodicRegistry::class);

            foreach ($configs['periodic'] as $periodic) {
                $def->addMethodCall('addPeriodic', [
                    new Reference(ltrim($periodic, '@')),
                ]);
            }
        }

        //server
        if (!empty($configs['servers'])) {
            $def = $container->getDefinition(ServerRegistry::class);

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
            $container->getDefinition(WampRouter::class)
                ->replaceArgument('$router', new Reference('gos_pubsub_router.websocket'));
        }

        // WAMP Pusher Configuration
        if (isset($configs['pushers']) && isset($configs['pushers']['wamp'])) {
            if (!is_bool($configs['pushers']['wamp']['ssl'])) {
                throw new \InvalidArgumentException(sprintf('The ssl node under wamp pusher configuration must be a boolean value'));
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

            $twigConfig = array('globals' => array(
                'gos_web_socket_server_host' => $config['server']['host'],
                'gos_web_socket_server_port' => $config['server']['port'],
            ));

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
