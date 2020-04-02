<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;

final class RegisterTwigGlobalsCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('twig') || !$container->hasParameter('gos_web_socket.shared_config') || !$container->getParameter('gos_web_socket.shared_config')) {
            return;
        }

        $definition = $container->getDefinition('twig');

        if ($container->hasParameter('gos_web_socket.server.host')) {
            $definition->addMethodCall(
                'addGlobal',
                [
                    'gos_web_socket_server_host',
                    new Parameter('gos_web_socket.server.host'),
                ]
            );
        }

        if ($container->hasParameter('gos_web_socket.server.port')) {
            $definition->addMethodCall(
                'addGlobal',
                [
                    'gos_web_socket_server_port',
                    new Parameter('gos_web_socket.server.port'),
                ]
            );
        }
    }
}
