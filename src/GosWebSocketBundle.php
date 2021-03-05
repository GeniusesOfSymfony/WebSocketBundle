<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RegisterTwigGlobalsCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RegisterWebsocketRouterResourcesCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\DependencyInjection\AddEventAliasesPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class GosWebSocketBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container
            ->addCompilerPass(new AddEventAliasesPass(GosWebSocketEvents::ALIASES))
            ->addCompilerPass(new RegisterTwigGlobalsCompilerPass())
            ->addCompilerPass(new RegisterWebsocketRouterResourcesCompilerPass())
        ;
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
