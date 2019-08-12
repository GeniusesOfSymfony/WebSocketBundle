<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\ServerPushHandlerCompilerPass;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerInterface;
use Gos\Bundle\WebSocketBundle\Pusher\ServerPushHandlerRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class ServerPushHandlerCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testServerPushHandlersAreAddedToTheRegistry()
    {
        $this->registerService('gos_web_socket.registry.server_push_handler', ServerPushHandlerRegistry::class);
        $this->registerService('test.server_push_handler', ServerPushHandlerInterface::class)
            ->addTag('gos_web_socket.push_handler', ['alias' => 'test']);

        $this->compile();

        $this->assertContainerBuilderHasService('test.server_push_handler', ServerPushHandlerInterface::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.registry.server_push_handler',
            'addPushHandler',
            [new Reference('test.server_push_handler')]
        );
    }

    public function testServerPushHandlersAreNotAddedToTheRegistryWhenTheAliasIsNotDefined()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Service "test.server_push_handler" must define the "alias" attribute on "gos_web_socket.push_handler" tags.'
        );

        $this->registerService('gos_web_socket.registry.server_push_handler', ServerPushHandlerRegistry::class);
        $this->registerService('test.server_push_handler', ServerPushHandlerInterface::class)
            ->addTag('gos_web_socket.push_handler');

        $this->compile();
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ServerPushHandlerCompilerPass());
    }
}
