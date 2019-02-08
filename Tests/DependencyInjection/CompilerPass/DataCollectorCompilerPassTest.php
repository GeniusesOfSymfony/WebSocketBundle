<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\DataCollectorCompilerPass;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampPusher;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Stopwatch\Stopwatch;

class DataCollectorCompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->container->setParameter('kernel.debug', false);
    }

    public function testPushersAreNotDecoratedInProductionMode()
    {
        $wampPusher = new Definition(WampPusher::class);
        $wampPusher->addTag('gos_web_socket.pusher');

        $this->setDefinition('gos_web_socket.wamp.pusher', $wampPusher);

        $this->compile();

        $this->assertContainerBuilderHasService('gos_web_socket.wamp.pusher', WampPusher::class);
    }

    public function testPushersAreDecoratedInDebugMode()
    {
        $this->container->setParameter('kernel.debug', true);

        $this->registerService('debug.stopwatch', Stopwatch::class);

        $wampPusher = new Definition(WampPusher::class);
        $wampPusher->addTag('gos_web_socket.pusher');

        $this->setDefinition('gos_web_socket.wamp.pusher', $wampPusher);

        $this->compile();

        $this->assertContainerBuilderHasService('gos_web_socket.wamp.pusher.data_collector', PusherDecorator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.wamp.pusher.data_collector',
            0,
            new Reference('gos_web_socket.wamp.pusher.data_collector.inner')
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DataCollectorCompilerPass());
    }
}
