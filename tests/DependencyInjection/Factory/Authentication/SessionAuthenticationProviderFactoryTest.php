<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection\Factory\Authentication;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\SessionAuthenticationProviderFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SessionAuthenticationProviderFactoryTest extends TestCase
{
    /**
     * @var SessionAuthenticationProviderFactory
     */
    private $factory;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp(): void
    {
        $this->factory = new SessionAuthenticationProviderFactory();
        $this->container = new ContainerBuilder();
    }

    public function testAuthenticationProviderServiceIsCreatedWithDefaultConfiguration(): void
    {
        $this->container->setParameter(
            'security.firewalls',
            [
                'dev',
                'main',
            ]
        );

        $this->factory->createAuthenticationProvider(
            $this->container,
            [
                'firewalls' => null,
            ],
        );

        self::assertTrue(
            $this->container->hasDefinition('gos_web_socket.authentication.provider.session.default'),
            'The authentication provider service should be registered to the container.'
        );

        /** @var ChildDefinition $definition */
        $definition = $this->container->getDefinition('gos_web_socket.authentication.provider.session.default');

        self::assertSame(
            'security.firewalls',
            (string) $definition->getArgument(1),
            'The firewalls argument should be mapped to the "security.firewalls" parameter.'
        );
    }

    public function testAuthenticationProviderServiceIsCreatedWithAnArrayOfFirewalls(): void
    {
        $this->factory->createAuthenticationProvider(
            $this->container,
            [
                'firewalls' => [
                    'dev',
                    'main',
                ],
            ],
        );

        self::assertTrue(
            $this->container->hasDefinition('gos_web_socket.authentication.provider.session.default'),
            'The authentication provider service should be registered to the container.'
        );

        /** @var ChildDefinition $definition */
        $definition = $this->container->getDefinition('gos_web_socket.authentication.provider.session.default');

        self::assertSame(
            ['dev', 'main'],
            $definition->getArgument(1),
            'The firewalls argument should be the configured firewalls.'
        );
    }

    public function testAuthenticationProviderServiceIsCreatedWithAStringFirewall(): void
    {
        $this->factory->createAuthenticationProvider(
            $this->container,
            [
                'firewalls' => 'main',
            ],
        );

        self::assertTrue(
            $this->container->hasDefinition('gos_web_socket.authentication.provider.session.default'),
            'The authentication provider service should be registered to the container.'
        );

        /** @var ChildDefinition $definition */
        $definition = $this->container->getDefinition('gos_web_socket.authentication.provider.session.default');

        self::assertSame(
            ['main'],
            $definition->getArgument(1),
            'A string firewall should be converted to an array.'
        );
    }
}
