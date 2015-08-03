<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PusherCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig('gos_web_socket');
        $configuration = new Configuration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);

        //pusher
        if (!isset($config['pushers']) || empty($config['pushers'])) {
            return;
        }

        //Remove pusher fill with only Configuration default value, better way ?
        $pushers = array_filter($config['pushers'], function ($value) {
            if (isset($value['host']) && isset($value['port'])) {
                return $value;
            }
        });

        //Pusher
        $definition = $container->getDefinition('gos_web_socket.pusher_registry');
        $taggedServices = $container->findTaggedServiceIds('gos_web_socket.pusher');

        foreach ($taggedServices as $id => $attributes) {
            $alias = $attributes[0]['alias'];

            if (!isset($pushers[$alias])) { //Pusher not configured
                $container->removeDefinition($id);
                continue;
            }

            $pusherDef = $container->getDefinition($id);
            $pusherDef
                ->addMethodCall('setName', [$alias])
                ->addMethodCall('setConfig', [$pushers[$alias]]);

            $definition->addMethodCall('addPusher', [new Reference($id), $alias]);
        }

        //ServerPushHandler
        $definition = $container->getDefinition('gos_web_socket.server_push_handler.registry');
        $taggedServices = $container->findTaggedServiceIds('gos_web_socket.push_handler');

        foreach ($taggedServices as $id => $attributes) {
            $alias = $attributes[0]['alias'];

            if (!isset($pushers[$alias])) { //ServerPushHandler not configured
                $container->removeDefinition($id);
                continue;
            }

            $pushHandlerDef = $container->getDefinition($id);
            $pushHandlerDef
                ->addMethodCall('setName', [$alias])
                ->addMethodCall('setConfig', [$pushers[$alias]]);

            $definition->addMethodCall('addPushHandler', [new Reference($id), $alias]);
        }
    }
}
