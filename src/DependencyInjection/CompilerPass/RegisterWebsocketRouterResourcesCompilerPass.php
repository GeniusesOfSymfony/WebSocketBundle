<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;

final class RegisterWebsocketRouterResourcesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('gos_pubsub_router.router.websocket')) {
            return;
        }

        $container->getDefinition('gos_pubsub_router.router.websocket')
            ->replaceArgument(2, new Parameter('gos_web_socket.router_resources'));
    }
}
