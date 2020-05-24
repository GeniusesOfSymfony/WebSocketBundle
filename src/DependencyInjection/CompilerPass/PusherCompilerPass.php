<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

trigger_deprecation('gos/web-socket-bundle', '3.1', 'The "%s" class is deprecated and will be removed in 4.0, use the symfony/messenger component instead.', PusherCompilerPass::class);

/**
 * @deprecated to be removed in 4.0, use the symfony/messenger component instead
 */
final class PusherCompilerPass implements CompilerPassInterface
{
    /**
     * @throws InvalidArgumentException if the service tag is missing required attributes
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('gos_web_socket.registry.pusher')) {
            $registryDefinition = $container->getDefinition('gos_web_socket.registry.pusher');

            foreach ($container->findTaggedServiceIds('gos_web_socket.pusher') as $id => $attributes) {
                if (!isset($attributes[0]['alias'])) {
                    throw new InvalidArgumentException(sprintf('Service "%s" must define the "alias" attribute on "gos_web_socket.pusher" tags.', $id));
                }

                $pusherDefinition = $container->getDefinition($id);
                $pusherDefinition->addMethodCall('setName', [$attributes[0]['alias']]);

                $registryDefinition->addMethodCall('addPusher', [new Reference($id)]);
            }
        }
    }
}
