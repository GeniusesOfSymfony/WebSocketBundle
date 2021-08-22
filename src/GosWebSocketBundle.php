<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\DataCollectorCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\PeriodicCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\PusherCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RegisterTwigGlobalsCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RegisterWebsocketRouterResourcesCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RpcCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\ServerCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\ServerPushHandlerCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\TopicCompilerPass;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\SessionAuthenticationProviderFactory;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
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
            ->addCompilerPass(new ServerCompilerPass())
            ->addCompilerPass(new RpcCompilerPass())
            ->addCompilerPass(new TopicCompilerPass())
            ->addCompilerPass(new PeriodicCompilerPass())
            ->addCompilerPass(new PusherCompilerPass(true))
            ->addCompilerPass(new ServerPushHandlerCompilerPass(true))
            ->addCompilerPass(new DataCollectorCompilerPass(true))
            ->addCompilerPass(new RegisterTwigGlobalsCompilerPass())
            ->addCompilerPass(new RegisterWebsocketRouterResourcesCompilerPass())
        ;

        /** @var GosWebSocketExtension $extension */
        $extension = $container->getExtension('gos_web_socket');
        $extension->addAuthenticationProviderFactory(new SessionAuthenticationProviderFactory());
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
