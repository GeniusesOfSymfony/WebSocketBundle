<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class RpcCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('gos_web_socket.rpc.registry');
        $taggedServices = $container->findTaggedServiceIds('gos_web_socket.rpc');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addRpc', [new Reference($id)]);
        }
    }
}
