<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

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
     * {@inheritDoc}
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

        $container->setParameter(
            'web_socket_firewalls',
            isset($configs['client']['firewall']) ? (array) $configs['client']['firewall'] : array()
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

        if (isset($configs['client'])) {
            $clientConf = $configs['client'];

            if (isset($clientConf['session_handler'])) {
                $def = $container->getDefinition('gos_web_socket.ws.server');
                $def->addMethodCall('setSessionHandler', [
                    new Reference(ltrim($clientConf['session_handler'], '@')),
                ]);
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

        if (!empty($configs['rpc'])) {
            $def = $container->getDefinition('gos_web_socket.rpc.registry');

            foreach ($configs['rpc'] as $rpc) {
                $def->addMethodCall('addRpc', [
                    new Reference(ltrim($rpc, '@')),
                ]);
            }
        }

        if (!empty($configs['topics'])) {
            $def = $container->getDefinition('gos_web_socket.topic.registry');

            foreach ($configs['topics'] as $topic) {
                $def->addMethodCall('addTopic', [
                    new Reference(ltrim($topic, '@')),
                ]);
            }
        }

        if (!empty($configs['periodic'])) {
            $def = $container->getDefinition('gos_web_socket.periodic.registry');

            foreach ($configs['periodic'] as $periodic) {
                $def->addMethodCall('addPeriodic', [
                    new Reference(ltrim($periodic, '@')),
                ]);
            }
        }

        if (!empty($configs['servers'])) {
            $def = $container->getDefinition('gos_web_socket.server.registry');

            foreach ($configs['servers'] as $server) {
                $def->addMethodCall('addServer', [
                    new Reference(ltrim($server, '@')),
                ]);
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
        $pubsubConfig = $config['server']['router'];
        $container->prependExtensionConfig('gos_pubsub_router', $pubsubConfig);

        //assetic
        if (isset($bundles['AsseticBundle'])) {
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
                            'VERBOSITY_NORMAL' => Logger::DEBUG,
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
