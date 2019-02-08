<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RpcCompilerPass;
use Gos\Bundle\WebSocketBundle\RPC\RpcInterface;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\RpcRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RpcCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry()
    {
        $this->registerService('gos_web_socket.rpc.registry', RpcRegistry::class);
        $this->registerService('test.rpc', RpcInterface::class)
            ->addTag('gos_web_socket.rpc');

        $this->compile();

        $this->assertContainerBuilderHasService('test.rpc', RpcInterface::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.rpc.registry',
            'addRpc',
            [new Reference('test.rpc')]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new RpcCompilerPass());
    }
}
