<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DataCollectorCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('kernel.debug') || !$container->hasDefinition('debug.stopwatch')) {
            return;
        }

        $pushers = $container->findTaggedServiceIds('gos_web_socket.pusher');

        foreach ($pushers as $id => $attributes) {
            $pusherInnerId = $id.'.inner';

            $container->setDefinition($pusherInnerId, $container->getDefinition($id));

            $collectingPusherDef = new Definition(
                PusherDecorator::class,
                [
                    new Reference($pusherInnerId),
                    new Reference('debug.stopwatch'),
                    new Reference('gos_web_socket.data_collector'),
                ]
            );
            $collectingPusherDef->setDecoratedService($id, $pusherInnerId);

            $container->setDefinition($id, $collectingPusherDef);
        }
    }
}
