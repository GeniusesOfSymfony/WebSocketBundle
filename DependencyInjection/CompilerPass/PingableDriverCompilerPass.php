<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PingableDriverCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasParameter('database_driver')) {
            return;
        }

        if (false === $container->hasAlias('gos_web_socket.session_handler')) {
            return;
        }

        $sessionHandlerDefinition = $container->getDefinition((string) $container->getAlias('gos_web_socket.session_handler'));

        if (!\in_array(\SessionHandlerInterface::class, \class_implements($sessionHandlerDefinition->getClass()), true)) {
            return;
        }

        $periodicRegistryDef = $container->getDefinition('gos_web_socket.periodic.registry');

        $pdoDriver = ['pdo_mysql', 'pdo_sqlite', 'pdo_pgsql'];

        if (in_array($container->getParameter('database_driver'), $pdoDriver)) {
            $periodicRegistryDef->addMethodCall(
                'addPeriodic', [new Reference('gos_web_socket.pdo.periodic_ping')]
            );
        }
    }
}
