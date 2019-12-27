<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
final class RpcCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('gos_web_socket.registry.rpc')) {
            return;
        }

        $definition = $container->getDefinition('gos_web_socket.registry.rpc');
        $taggedServices = $container->findTaggedServiceIds('gos_web_socket.rpc');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addRpc', [new Reference($id)]);
        }
    }
}
