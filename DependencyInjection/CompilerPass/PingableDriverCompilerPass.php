<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

trigger_deprecation('gos/web-socket-bundle', '1.9', 'The %s class is deprecated will be removed in 2.0.', PingableDriverCompilerPass::class);

/**
 * @deprecated to be removed in 2.0.
 */
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

        if (PdoSessionHandler::class !== $sessionHandlerDefinition->getClass() && ( !class_exists($sessionHandlerDefinition->getClass()) || !\in_array(PdoSessionHandler::class, \class_parents($sessionHandlerDefinition->getClass()), true))) {
            return;
        }

        if (!$container->hasDefinition('pdo') || !$container->hasAlias('pdo')) {
            $pdoReference = $sessionHandlerDefinition->getArgument(0);
            if (!$pdoReference instanceof Reference || \PDO::class !== $container->getDefinition((string) $pdoReference)->getClass()) {
                return;
            }
            $periodicPingDefinition = $container->getDefinition('gos_web_socket.pdo.periodic_ping');
            $periodicPingDefinition->setArgument(0, $pdoReference);
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
