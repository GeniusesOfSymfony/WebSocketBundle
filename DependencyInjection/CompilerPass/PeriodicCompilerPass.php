<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class PeriodicCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition(PeriodicRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('gos_web_socket.periodic');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addPeriodic', [new Reference($id)]);
        }
    }
}
