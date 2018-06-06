<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use React\EventLoop\Factory;
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
        $definition = $container->getDefinition(Factory::class);

        if (method_exists($definition, 'setFactory')) {
            $definition->setFactory('React\EventLoop\Factory::create');
        }
    }
}
