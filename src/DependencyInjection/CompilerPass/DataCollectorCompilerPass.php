<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\Pusher\DataCollectingPusherDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class DataCollectorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->getParameter('kernel.debug') || !$container->hasDefinition('debug.stopwatch')) {
            return;
        }

        $pushers = $container->findTaggedServiceIds('gos_web_socket.pusher');

        foreach ($pushers as $id => $attributes) {
            $collectorId = $id.'.data_collector';

            $collectingPusherDef = new Definition(
                DataCollectingPusherDecorator::class,
                [
                    new Reference($collectorId.'.inner'),
                    new Reference('debug.stopwatch'),
                    new Reference('gos_web_socket.data_collector.websocket'),
                ]
            );
            $collectingPusherDef->setDecoratedService($id);

            $container->setDefinition($collectorId, $collectingPusherDef);
        }
    }
}
