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
        $sessionHandler = $container->get('gos_web_socket.session_handler', Container::NULL_ON_INVALID_REFERENCE);

        if(null === $sessionHandler){
            return;
        }

        $periodicRegistryDef = $container->getDefinition('gos_web_socket.periodic.registry');

        if($sessionHandler instanceof PdoSessionHandler){
            $periodicRegistryDef->addMethodCall(
                'addPeriodic', [new Reference('gos_web_socket.pdo.periodic_ping')]
            );
        }
    }
}