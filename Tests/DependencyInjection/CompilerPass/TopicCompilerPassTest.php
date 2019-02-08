<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\TopicCompilerPass;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TopicCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry()
    {
        $registryDefinition = $this->registerService('gos_web_socket.topic.registry', TopicRegistry::class);

        $rpcService = new Definition(TopicInterface::class);
        $rpcService->addTag('gos_web_socket.topic');

        $this->setDefinition('test.topic', $rpcService);

        $this->compile();

        $this->assertContainerBuilderHasService('test.topic', TopicInterface::class);
        $this->assertCount(1, $registryDefinition->getMethodCalls());
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TopicCompilerPass());
    }
}
