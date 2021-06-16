<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\Pusher\DataCollectingPusherDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class DataCollectorCompilerPass implements CompilerPassInterface
{
    private bool $internal;

    /**
     * @param bool $internal Flag indicating the pass was created by an internal bundle call (used to suppress runtime deprecations)
     */
    public function __construct(bool $internal = false)
    {
        $this->internal = $internal;
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$this->internal) {
            trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', self::class);
        }

        if (!$container->getParameter('kernel.debug') || !$container->hasDefinition('debug.stopwatch')) {
            return;
        }

        $pushers = $container->findTaggedServiceIds('gos_web_socket.pusher');

        $usesSymfony51Api = method_exists(Definition::class, 'getDeprecation');

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

            if ($usesSymfony51Api) {
                $collectingPusherDef->setDeprecated(
                    'gos/web-socket-bundle',
                    '3.1',
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the symfony/messenger component instead.'
                );
            } else {
                $collectingPusherDef->setDeprecated(
                    true,
                    'The "%service_id%" service is deprecated and will be removed in GosWebSocketBundle 4.0, use the symfony/messenger component instead.'
                );
            }

            $container->setDefinition($collectorId, $collectingPusherDef);
        }
    }
}
