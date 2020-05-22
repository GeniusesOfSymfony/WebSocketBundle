<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s class is deprecated will be removed in 2.0.', LoopFactoryCompilerPass::class);

/**
 * @deprecated to be removed in 2.0.
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
