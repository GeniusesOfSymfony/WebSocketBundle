<?php

namespace Gos\Bundle\WebSocketBundle;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\PeriodicCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\PingableDriverCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RpcCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\ServerCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\TopicCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class GosWebSocketBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ServerCompilerPass());
        $container->addCompilerPass(new RpcCompilerPass());
        $container->addCompilerPass(new TopicCompilerPass());
        $container->addCompilerPass(new PeriodicCompilerPass());
        $container->addCompilerPass(new PingableDriverCompilerPass());
    }
}
