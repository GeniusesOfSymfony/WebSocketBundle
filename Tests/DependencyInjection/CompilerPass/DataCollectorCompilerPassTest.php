<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DataCollector\PusherDecorator;
use Gos\Bundle\WebSocketBundle\DataCollector\WebsocketDataCollector;
use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\DataCollectorCompilerPass;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampPusher;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\Compiler\DecoratorServicePass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Stopwatch\Stopwatch;

class DataCollectorCompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->container->getCompilerPassConfig()->addPass(new DecoratorServicePass(), PassConfig::TYPE_OPTIMIZE);
        $this->container->setParameter('kernel.debug', false);
    }

    public function testPushersAreNotDecoratedInProductionMode(): void
    {
        $wampPusher = new Definition(WampPusher::class);
        $wampPusher->addTag('gos_web_socket.pusher');

        $this->setDefinition('gos_web_socket.pusher.wamp', $wampPusher);

        $this->compile();

        $this->assertContainerBuilderHasService('gos_web_socket.pusher.wamp', WampPusher::class);
    }

    public function testPushersAreDecoratedInDebugMode(): void
    {
        $this->container->setParameter('kernel.debug', true);

        $this->registerService('debug.stopwatch', Stopwatch::class);
        $this->registerService('gos_web_socket.data_collector.websocket', WebsocketDataCollector::class);

        $wampPusher = new Definition(WampPusher::class);
        $wampPusher->addTag('gos_web_socket.pusher');

        $this->setDefinition('gos_web_socket.pusher.wamp', $wampPusher);

        $this->compile();

        $this->assertContainerBuilderHasService('gos_web_socket.pusher.wamp.data_collector', PusherDecorator::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.pusher.wamp.data_collector',
            0,
            new Reference('gos_web_socket.pusher.wamp.data_collector.inner')
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DataCollectorCompilerPass());
    }
}
