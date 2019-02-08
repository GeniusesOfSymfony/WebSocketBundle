<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\PeriodicCompilerPass;
use Gos\Bundle\WebSocketBundle\Periodic\DoctrinePeriodicPing;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\PeriodicRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class PeriodicCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry()
    {
        $registryDefinition = $this->registerService('gos_web_socket.periodic.registry', PeriodicRegistry::class);

        $periodicService = new Definition(DoctrinePeriodicPing::class);
        $periodicService->addTag('gos_web_socket.periodic');

        $this->setDefinition('test.periodic.doctrine', $periodicService);

        $this->compile();

        $this->assertContainerBuilderHasService('test.periodic.doctrine', DoctrinePeriodicPing::class);
        $this->assertCount(1, $registryDefinition->getMethodCalls());
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PeriodicCompilerPass());
    }
}
