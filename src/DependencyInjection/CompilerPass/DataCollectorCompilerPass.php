<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\Pusher\DataCollectingPusherDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', DataCollectorCompilerPass::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
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
            $collectingPusherDef->setDeprecated(true, 'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the symfony/messenger component instead.');
            $collectingPusherDef->setDecoratedService($id);

            $container->setDefinition($collectorId, $collectingPusherDef);
        }
    }
}
