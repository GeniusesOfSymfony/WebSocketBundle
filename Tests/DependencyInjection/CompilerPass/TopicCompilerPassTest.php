<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\TopicCompilerPass;
use Gos\Bundle\WebSocketBundle\Server\App\Registry\TopicRegistry;
use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TopicCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testPeriodicHandlersAreAddedToTheRegistry()
    {
        $this->registerService('gos_web_socket.topic.registry', TopicRegistry::class);
        $this->registerService('test.topic', TopicInterface::class)
            ->addTag('gos_web_socket.topic');

        $this->compile();

        $this->assertContainerBuilderHasService('test.topic', TopicInterface::class);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.topic.registry',
            'addTopic',
            [new Reference('test.topic')]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new TopicCompilerPass());
    }
}
