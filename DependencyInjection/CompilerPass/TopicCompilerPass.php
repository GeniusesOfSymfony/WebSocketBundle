<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class TopicCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition(TopicRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('gos_web_socket.topic');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall('addTopic', [new Reference($id)]);
        }
    }
}
