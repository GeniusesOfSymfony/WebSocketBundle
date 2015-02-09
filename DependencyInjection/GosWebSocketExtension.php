<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

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

        if (isset($configs['session_handler'])) {
            $def = $container->getDefinition('gos_web_socket.ws.server');
            $def->addMethodCall('setSessionHandler', [
                new Reference(ltrim($configs['session_handler'], '@'))
            ]);
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
    }
}
