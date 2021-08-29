<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection;

use Doctrine\DBAL\Connection;
use Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\Driver\StorageDriverInterface;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\SessionAuthenticationProviderFactory;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
use Gos\Bundle\WebSocketBundle\GosWebSocketBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
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

        // Authentication services
        $this->assertContainerBuilderHasAlias('gos_web_socket.authentication.storage.driver', 'gos_web_socket.authentication.storage.driver.in_memory');
        $this->assertContainerBuilderHasAlias(StorageDriverInterface::class, 'gos_web_socket.authentication.storage.driver.in_memory');
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

    public function testContainerIsLoadedWithSessionAuthenticationProviderConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $bundleConfig = [
            'authentication' => [
                'providers' => [
                    'session' => [
                        'firewalls' => 'main',
                    ],
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.authentication.authenticator',
            0,
            new IteratorArgument([new Reference('gos_web_socket.authentication.provider.session.default')])
        );
    }

    public function testContainerIsLoadedWithPsrCacheAuthenticationStorageConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $bundleConfig = [
            'authentication' => [
                'storage' => [
                    'type' => Configuration::AUTHENTICATION_STORAGE_TYPE_PSR_CACHE,
                    'pool' => 'cache.websocket',
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasAlias('gos_web_socket.authentication.storage.driver', 'gos_web_socket.authentication.storage.driver.psr_cache');
        $this->assertContainerBuilderHasAlias(StorageDriverInterface::class, 'gos_web_socket.authentication.storage.driver.psr_cache');

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.authentication.storage.driver.psr_cache',
            0,
            new Reference('cache.websocket')
        );
    }

    public function testContainerIsLoadedWithServiceAuthenticationStorageConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $bundleConfig = [
            'authentication' => [
                'storage' => [
                    'type' => Configuration::AUTHENTICATION_STORAGE_TYPE_SERVICE,
                    'id' => 'app.authentication.storage.driver.custom',
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasAlias('gos_web_socket.authentication.storage.driver', 'app.authentication.storage.driver.custom');
        $this->assertContainerBuilderHasAlias(StorageDriverInterface::class, 'app.authentication.storage.driver.custom');
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
        $extension = new GosWebSocketExtension();
        $extension->addAuthenticationProviderFactory(new SessionAuthenticationProviderFactory());

        return [
            $extension,
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
