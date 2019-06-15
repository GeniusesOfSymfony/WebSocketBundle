<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\ServerCompilerPass;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ServerCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry()
    {
        $this->registerService('gos_web_socket.server.registry', ServerRegistry::class);
        $this->registerService('test.server', ServerInterface::class)
            ->addTag('gos_web_socket.server');

        $this->compile();

        $this->assertContainerBuilderHasService('test.server', ServerInterface::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.server.registry',
            'addServer',
            [new Reference('test.server')]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ServerCompilerPass());
    }
}
