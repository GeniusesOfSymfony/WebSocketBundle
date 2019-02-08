<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\ServerCompilerPass;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\ServerRegistry;
use Gos\Bundle\WebSocketBundle\Server\Type\ServerInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ServerCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry()
    {
        $registryDefinition = $this->registerService('gos_web_socket.server.registry', ServerRegistry::class);

        $rpcService = new Definition(ServerInterface::class);
        $rpcService->addTag('gos_web_socket.server');

        $this->setDefinition('test.server', $rpcService);

        $this->compile();

        $this->assertContainerBuilderHasService('test.server', ServerInterface::class);
        $this->assertCount(1, $registryDefinition->getMethodCalls());
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ServerCompilerPass());
    }
}
