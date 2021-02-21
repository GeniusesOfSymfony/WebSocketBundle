<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\PeriodicCompilerPass;
use Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PeriodicCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry(): void
    {
        $this->registerService('gos_web_socket.registry.periodic', PeriodicRegistry::class);
        $this->registerService('test.periodic.doctrine', DoctrinePeriodicPing::class)
            ->addTag('gos_web_socket.periodic');

        $this->compile();

        $this->assertContainerBuilderHasService('test.periodic.doctrine', DoctrinePeriodicPing::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.registry.periodic',
            'addPeriodic',
            [new Reference('test.periodic.doctrine')]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PeriodicCompilerPass());
    }
}
