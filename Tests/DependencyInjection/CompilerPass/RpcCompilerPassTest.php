<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RpcCompilerPass;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class RpcCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry()
    {
        $registryDefinition = $this->registerService('gos_web_socket.rpc.registry', RpcRegistry::class);

        $rpcService = new Definition(RpcInterface::class);
        $rpcService->addTag('gos_web_socket.rpc');

        $this->setDefinition('test.rpc', $rpcService);

        $this->compile();

        $this->assertContainerBuilderHasService('test.rpc', RpcInterface::class);
        $this->assertCount(1, $registryDefinition->getMethodCalls());
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RpcCompilerPass());
    }
}
