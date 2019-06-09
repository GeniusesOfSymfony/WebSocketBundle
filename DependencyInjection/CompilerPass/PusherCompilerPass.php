<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class PusherCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition('gos_web_socket.pusher_registry')) {
            $registryDefinition = $container->getDefinition('gos_web_socket.pusher_registry');

            foreach ($container->findTaggedServiceIds('gos_web_socket.pusher') as $id => $attributes) {
                if (!isset($attributes[0]['alias'])) {
                    throw new InvalidArgumentException(
                        sprintf('Service "%s" must define the "alias" attribute on "gos_web_socket.pusher" tags.', $id)
                    );
                }

                $pusherDefinition = $container->getDefinition($id);
                $pusherDefinition->addMethodCall('setName', [$attributes[0]['alias']]);

                $registryDefinition->addMethodCall('addPusher', [new Reference($id)]);
            }
        }
    }
}
