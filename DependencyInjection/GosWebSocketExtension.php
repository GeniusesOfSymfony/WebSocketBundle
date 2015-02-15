<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Monolog\Logger;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Output\OutputInterface;
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
        //If assetic is loaded, auto register the bundle
        if ($container->hasParameter('assetic.bundles')) {
            $asseticBundles = $container->getParameter('assetic.bundles');
            $asseticBundles[] = 'GosWebSocketBundle';
            $container->setParameter('assetic.bundles', $asseticBundles);
        }

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config/services')
        );

        $loader->load('services.yml');

        $configuration = new Configuration();
        $configs = $this->processConfiguration($configuration, $configs);

        $container->setParameter(
            'web_socket_server.port',
            $configs['web_socket_server']['port']
        );

        $container->setParameter(
            'web_socket_server.host',
            $configs['web_socket_server']['host']
        );

        if(isset($configs['client'])){
            $clientConf = $configs['client'];

            if (!isset($clientConf['session_handler'])) {
                throw new \Exception('You must define session provider if you want configure "Client" options');
            }

            $def = $container->getDefinition('gos_web_socket.ws.server');
            $def->addMethodCall('setSessionHandler', [
                new Reference(ltrim($clientConf['session_handler'], '@'))
            ]);

            if(!isset($clientConf['firewall'])){
                throw new \Exception('You must define at leat one element against wish firewall user must be auth');
            }

            $clientListenerDef = $container->getDefinition('gos_web_socket.client_event.listener');
            $clientListenerDef->addArgument((array) $clientConf['firewall']);

            if(isset($clientConf['storage']['driver'])){

                $driverRef = ltrim($clientConf['storage']['driver'], '@');
                $clientStorageDef = $container->getDefinition('gos_web_socket.client_storage');

                if(isset($clientConf['storage']['decorator'])){
                    $decoratorRef = ltrim($clientConf['storage']['decorator'], '@');
                    $decoratorDef = $container->getDefinition($decoratorRef);
                    $decoratorDef->addArgument(new Reference($driverRef));

                    $clientStorageDef->addMethodCall('setStorageDriver', [
                        new Reference($decoratorRef)
                    ]);
                }else{
                    $clientStorageDef->addMethodCall('setStorageDriver', [
                        new Reference($driverRef)
                    ]);
                }
            }
        }

        if (!empty($configs['rpc'])) {
            $def = $container->getDefinition('gos_web_socket.rpc.registry');

            foreach ($configs['rpc'] as $rpc) {
                $def->addMethodCall('addRpc', [
                    new Reference(ltrim($rpc, '@'))
                ]);
            }
        }

        if (!empty($configs['topic'])) {
            $def = $container->getDefinition('gos_web_socket.topic.registry');

            foreach ($configs['topic'] as $rpc) {
                $def->addMethodCall('addTopic', [
                    new Reference(ltrim($rpc, '@'))
                ]);
            }
        }

        if (!empty($configs['periodic'])) {
            $def = $container->getDefinition('gos_web_socket.periodic.registry');

            foreach ($configs['periodic'] as $rpc) {
                $def->addMethodCall('addPeriodic', [
                    new Reference(ltrim($rpc, '@'))
                ]);
            }
        }

        if (!empty($configs['server'])) {
            $def = $container->getDefinition('gos_web_socket.server.registry');

            foreach ($configs['server'] as $rpc) {
                $def->addMethodCall('addServer', [
                    new Reference(ltrim($rpc, '@'))
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

        //twig
        if (isset($config['shared_config']) && true === $config['shared_config']) {
            if (!isset($bundles['TwigBundle'])) {
                throw new \Exception('Share option required Twig Bundle');
            }

            $twigConfig = array('globals' => array(
                'gos_web_socket_server_host' => $config['web_socket_server']['host'],
                'gos_web_socket_server_port' => $config['web_socket_server']['port'],
            ));

            $container->prependExtensionConfig('twig', $twigConfig);
        }

        //monolog
        if(isset($bundles['MonologBundle'])){
            $monologConfig = array(
                'channels' => array('websocket'),
                'handlers' => array(
                    'websocket' => array(
                        'type' => 'console',
                        'verbosity_levels' => array(
                            OutputInterface::VERBOSITY_NORMAL => Logger::INFO
                        ),
                        'channels' => array(
                            'type' => 'inclusive',
                            'elements' => array('websocket')
                        )
                    )
                )
            );

            $container->prependExtensionConfig('monolog', $monologConfig);
        }
    }
}
