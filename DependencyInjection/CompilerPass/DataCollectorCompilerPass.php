<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataCollectorCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('kernel.debug') || !$container->has('debug.stopwatch')) {
            return;
        }

        $pushers = $container->findTaggedServiceIds('gos_web_socket.pusher');

        foreach ($pushers as $id => $attributes) {
            $newPusherId = $id.'.base';
            $pusherDef = $container->getDefinition($id);
            $container->removeDefinition($id);
            $container->setDefinition($newPusherId, $pusherDef);

            $container->register($id, 'Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator')
                ->addArgument(new Reference($id.'.inner'))
                ->addArgument(new Reference('debug.stopwatch'))
                ->addArgument(new Reference('gos_web_socket.data_collector'))
                ->setDecoratedService($newPusherId)
            ;
        }
    }
}
