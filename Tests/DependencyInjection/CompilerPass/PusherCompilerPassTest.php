<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\PusherCompilerPass;
use Gos\Bundle\WebSocketBundle\Pusher\PusherInterface;
use Gos\Bundle\WebSocketBundle\Pusher\PusherRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

class PusherCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPushersAreAddedToTheRegistry()
    {
        $this->registerService('gos_web_socket.registry.pusher', PusherRegistry::class);
        $this->registerService('test.pusher', PusherInterface::class)
            ->addTag('gos_web_socket.pusher', ['alias' => 'test']);

        $this->compile();

        $this->assertContainerBuilderHasService('test.pusher', PusherInterface::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.registry.pusher',
            'addPusher',
            [new Reference('test.pusher')]
        );
    }

    public function testPushersAreNotAddedToTheRegistryWhenTheAliasIsNotDefined()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service "test.pusher" must define the "alias" attribute on "gos_web_socket.pusher" tags.');

        $this->registerService('gos_web_socket.registry.pusher', PusherRegistry::class);
        $this->registerService('test.pusher', PusherInterface::class)
            ->addTag('gos_web_socket.pusher');

        $this->compile();
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PusherCompilerPass());
    }
}
