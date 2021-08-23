<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection;

use Doctrine\DBAL\Connection;
use Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle;
use Gos\Bundle\WebSocketBundle\Authentication\Storage\TokenStorageInterface;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Factory\Authentication\SessionAuthenticationProviderFactory;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
use Gos\Bundle\WebSocketBundle\GosWebSocketBundle;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactory;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactory;
use Gos\Component\WebSocketClient\Wamp\Client;
use Gos\Component\WebSocketClient\Wamp\ClientFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @group legacy
 */
class GosWebSocketExtensionTest extends AbstractExtensionTestCase
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

        // Client storage container parameters
        $this->assertContainerBuilderHasParameter('gos_web_socket.client.storage.ttl');
        $this->assertContainerBuilderHasParameter('gos_web_socket.client.storage.prefix');

        // Pusher services
        $this->assertContainerBuilderNotHasService('gos_web_socket.pusher.amqp');
        $this->assertContainerBuilderNotHasService('gos_web_socket.pusher.wamp');
        $this->assertContainerBuilderNotHasService('gos_web_socket.pusher.amqp.push_handler');

        // Websocket client services
        $this->assertContainerBuilderNotHasService('gos_web_socket.websocket_client_factory');
        $this->assertContainerBuilderNotHasService('gos_web_socket.websocket_client');

        // Authentication services
        $this->assertContainerBuilderHasAlias('gos_web_socket.authentication.storage.driver', 'gos_web_socket.authentication.storage.driver.in_memory');
        $this->assertContainerBuilderHasAlias(TokenStorageInterface::class, 'gos_web_socket.authentication.storage.driver.in_memory');
    }

    public function testContainerFailsToLoadWhenPubSubBundleIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The GosWebSocketBundle requires the GosPubSubRouterBundle.');

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

    public function testContainerIsLoadedWithAuthenticatorEnabled(): void
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
                'enable_authenticator' => true,
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.event_listener.client',
            0,
            new Reference('gos_web_socket.authentication.token_storage')
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'gos_web_socket.server.application.wamp',
            3,
            new Reference('gos_web_socket.authentication.token_storage')
        );

        $this->assertContainerBuilderNotHasService('gos_web_socket.client.authentication.websocket_provider');
        $this->assertContainerBuilderNotHasService('gos_web_socket.client.driver.doctrine_cache');
        $this->assertContainerBuilderNotHasService('gos_web_socket.client.driver.in_memory');
        $this->assertContainerBuilderNotHasService('gos_web_socket.client.driver.symfony_cache');
        $this->assertContainerBuilderNotHasService('gos_web_socket.client.manipulator');
        $this->assertContainerBuilderNotHasService('gos_web_socket.client.storage');
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
        $this->assertContainerBuilderHasAlias(TokenStorageInterface::class, 'gos_web_socket.authentication.storage.driver.psr_cache');

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
        $this->assertContainerBuilderHasAlias(TokenStorageInterface::class, 'app.authentication.storage.driver.custom');
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

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.registry.origins',
            'addOrigin',
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
                        'prefix' => '',
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
                        'prefix' => '',
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

    public function testContainerIsLoadedWithWebsocketClientConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $bundleConfig = [
            'websocket_client' => [
                'enabled' => true,
                'host' => '127.0.0.1',
                'port' => 1337,
                'ssl' => false,
                'origin' => null,
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasService('gos_web_socket.websocket_client', Client::class);
        $this->assertContainerBuilderHasService('gos_web_socket.websocket_client_factory', ClientFactory::class);
    }

    public function testContainerIsLoadedWithWampPusherConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $bundleConfig = [
            'pushers' => [
                'wamp' => [
                    'enabled' => true,
                    'host' => '127.0.0.1',
                    'port' => 1337,
                    'ssl' => false,
                    'origin' => null,
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasService('gos_web_socket.pusher.wamp');
        $this->assertContainerBuilderHasService('gos_web_socket.pusher.wamp.connection_factory', WampConnectionFactory::class);
    }

    public function testContainerIsLoadedWithAmqpPusherConfigured(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $bundleConfig = [
            'pushers' => [
                'amqp' => [
                    'enabled' => true,
                    'host' => '127.0.0.1',
                    'port' => 5672,
                    'login' => 'username',
                    'password' => 'password',
                    'vhost' => '/',
                    'read_timeout' => 0,
                    'write_timeout' => 0,
                    'connect_timeout' => 0,
                    'queue_name' => 'gos_websocket',
                    'exchange_name' => 'gos_websocket_exchange',
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasService('gos_web_socket.pusher.amqp');
        $this->assertContainerBuilderHasService('gos_web_socket.pusher.amqp.push_handler');
        $this->assertContainerBuilderHasService('gos_web_socket.pusher.amqp.connection_factory', AmqpConnectionFactory::class);
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
            'authentication' => [
                'enable_authenticator' => false,
            ],
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
            ],
        ];
    }
}
