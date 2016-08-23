<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

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

        $sessionHandler = $container->get((string) $container->getAlias('gos_web_socket.session_handler'));

        if (false === $sessionHandler instanceof PdoSessionHandler) {
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
