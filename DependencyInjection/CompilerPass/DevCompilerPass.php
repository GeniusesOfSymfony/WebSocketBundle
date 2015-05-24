<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DevCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->getParameter('kernel.debug')) {
            return;
        }

        $periodicRegistryDef = $container->getDefinition('gos_web_socket.periodic.registry');

        $periodicRegistryDef->addMethodCall(
            'addPeriodic', [new Reference('gos_web_socket.memory_usage.periodic')]
        );
    }
}
