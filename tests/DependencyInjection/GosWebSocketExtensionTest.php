<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection;

use Doctrine\DBAL\Connection;
use Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
use Gos\Bundle\WebSocketBundle\GosWebSocketBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

final class GosWebSocketExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoadedWithDefaultConfiguration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->load();

        $this->assertContainerBuilderHasParameter('gos_web_socket.client.storage.ttl');
    }

    public function testContainerFailsToLoadWhenPubSubBundleIsMissing(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The GosWebSocketBundle requires the GosPubSubRouterBundle, please run "composer require gos/pubsub-router-bundle".');

        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->load();
    }

    public function testContainerIsLoadedWithPubSubBundleIntegration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $this->load();

        $this->assertContainerBuilderHasParameter('gos_web_socket.router_resources', []);
    }

    public function testContainerIsLoadedWithPubSubBundleIntegrationAndConvertingLegacyConfigurationToNewerConfiguration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $bundleConfig = [
            'server' => [
                'router' => [
                    'resources' => [
                        'example.yaml',
                    ],
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasParameter(
            'gos_web_socket.router_resources',
            [
                [
                    'resource' => 'example.yaml',
                    'type' => null,
                ],
            ]
        );
    }

    public function testContainerIsLoadedWithPubSubBundleIntegrationAndNewerConfiguration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $bundleConfig = [
            'server' => [
                'router' => [
                    'resources' => [
                        [
                            'resource' => 'example.yaml',
                            'type' => null,
                        ],
                    ],
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasParameter(
            'gos_web_socket.router_resources',
            [
                [
                    'resource' => 'example.yaml',
                    'type' => null,
                ],
            ]
        );
    }

    public function testContainerIsLoadedWithOriginsConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $this->load(
            [
                'origins' => [
                    'github.com',
                ],
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.registry.origins',
            0,
            ['github.com']
        );
    }

    public function testContainerIsLoadedWithClientConfiguredWithoutCacheDecorator(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $this->load(
            [
                'client' => [
                    'session_handler' => 'session.handler.pdo',
                    'firewall' => 'ws_firewall',
                    'storage' => [
                        'driver' => 'gos_web_socket.client.driver.in_memory',
                        'ttl' => 900,
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter('gos_web_socket.firewall');
        $this->assertContainerBuilderHasAlias('gos_web_socket.session_handler');

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.server.builder',
            'setSessionHandler',
            [new Reference('session.handler.pdo')]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.client.storage',
            0,
            new Reference('gos_web_socket.client.driver.in_memory')
        );
    }

    public function testContainerIsLoadedWithClientConfiguredWithCacheDecorator(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $this->load(
            [
                'client' => [
                    'session_handler' => 'session.handler.pdo',
                    'firewall' => 'ws_firewall',
                    'storage' => [
                        'driver' => 'gos_web_socket.client.driver.in_memory',
                        'ttl' => 900,
                        'decorator' => 'gos_web_socket.client.driver.symfony_cache',
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter('gos_web_socket.firewall');
        $this->assertContainerBuilderHasAlias('gos_web_socket.session_handler');

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.server.builder',
            'setSessionHandler',
            [new Reference('session.handler.pdo')]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.client.storage',
            0,
            new Reference('gos_web_socket.client.driver.symfony_cache')
        );
    }

    public function testContainerIsLoadedWithPingServicesConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);
        $this->container->setParameter('kernel.project_dir', __DIR__);

        $this->registerService('database_connection', Connection::class);
        $this->registerService('pdo', \PDO::class);

        $this->load(
            [
                'ping' => [
                    'services' => [
                        [
                            'name' => 'database_connection',
                            'type' => Configuration::PING_SERVICE_TYPE_DOCTRINE,
                            'interval' => 30,
                        ],
                        [
                            'name' => 'pdo',
                            'type' => Configuration::PING_SERVICE_TYPE_PDO,
                            'interval' => 20,
                        ],
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('gos_web_socket.periodic_ping.doctrine.database_connection', 1, 30);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('gos_web_socket.periodic_ping.pdo.pdo', 1, 20);

        $this->assertContainerBuilderHasServiceDefinitionWithTag('gos_web_socket.periodic_ping.doctrine.database_connection', 'gos_web_socket.periodic');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('gos_web_socket.periodic_ping.pdo.pdo', 'gos_web_socket.periodic');
    }

    protected function getContainerExtensions(): array
    {
        return [
            new GosWebSocketExtension(),
        ];
    }

    protected function getMinimalConfiguration(): array
    {
        return [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
            ],
        ];
    }
}
