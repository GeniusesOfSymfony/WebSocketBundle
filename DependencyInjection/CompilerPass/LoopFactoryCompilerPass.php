<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class LoopFactoryCompilerPass
 *
 * @package Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass
 */
class LoopFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('gos_web_socket.server.event_loop');

        if (method_exists($definition, 'setFactory')) {
            $definition->setFactory('React\EventLoop\Factory::create');
        } else {
            // SF < 2.6
            $definition
                ->setFactoryClass('React\EventLoop\Factory')
                ->setFactoryMethod('create');
        }
    }
}
