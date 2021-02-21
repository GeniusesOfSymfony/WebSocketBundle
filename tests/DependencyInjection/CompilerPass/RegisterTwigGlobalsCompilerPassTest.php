<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\CompilerPass;

use Gos\Bundle\WebSocketBundle\DependencyInjection\CompilerPass\RegisterTwigGlobalsCompilerPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Parameter;
use Twig\Environment;

final class RegisterTwigGlobalsCompilerPassTest extends AbstractCompilerPassTestCase
{
    public function testGlobalsAreNotAddedToTwigWhenSharedConfigParameterIsNotSet(): void
    {
        $this->registerService('twig', Environment::class);

        $this->compile();

        $this->assertEmpty($this->container->getDefinition('twig')->getMethodCalls());
    }

    public function testGlobalsAreAddedToTwig(): void
    {
        $this->registerService('twig', Environment::class);
        $this->container->setParameter('gos_web_socket.shared_config', true);
        $this->container->setParameter('gos_web_socket.server.host', '127.0.0.1');
        $this->container->setParameter('gos_web_socket.server.port', 8080);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'twig',
            'addGlobal',
            ['gos_web_socket_server_host', new Parameter('gos_web_socket.server.host')]
        );
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'twig',
            'addGlobal',
            ['gos_web_socket_server_port', new Parameter('gos_web_socket.server.port')]
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new RegisterTwigGlobalsCompilerPass());
    }
}
