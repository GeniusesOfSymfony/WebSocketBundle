<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\PubSubRouterBundle\Router\Router;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RegisterWebsocketRouterResourcesCompilerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

final class RegisterWebsocketRouterResourcesCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testTheRouterResourcesAreNotChangedIfTheParameterIsMissing(): void
    {
        $this->registerService('gos_pubsub_router.routing.loader', DelegatingLoader::class);

        $this->registerService('gos_pubsub_router.router.websocket', Router::class)
            ->addArgument('websocket')
            ->addArgument(new Reference('gos_pubsub_router.routing.loader'))
            ->addArgument([])
            ->addArgument([]);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('gos_pubsub_router.router.websocket', 2, []);
    }

    public function testResourcesAreAddedToTheRouter(): void
    {
        $this->registerService('gos_pubsub_router.routing.loader', DelegatingLoader::class);

        $this->registerService('gos_pubsub_router.router.websocket', Router::class)
            ->addArgument('websocket')
            ->addArgument(new Reference('gos_pubsub_router.routing.loader'))
            ->addArgument([])
            ->addArgument([]);

        $this->container->setParameter(
            'gos_web_socket.router_resources',
            [
                [
                    'resource' => 'example.yaml',
                    'type' => null,
                ],
            ]
        );

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('gos_pubsub_router.router.websocket', 2, new Parameter('gos_web_socket.router_resources'));
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterWebsocketRouterResourcesCompilerPass());
    }
}
