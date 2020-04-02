<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection;

use Doctrine\DBAL\Connection;
use Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle;
use Gos\Bundle\PubSubRouterBundle\Loader\XmlFileLoader;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
use Gos\Bundle\WebSocketBundle\GosWebSocketBundle;
use Gos\Bundle\WebSocketBundle\Pusher\Amqp\AmqpConnectionFactory;
use Gos\Bundle\WebSocketBundle\Pusher\Wamp\WampConnectionFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Monolog\Logger;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\DependencyInjection\Reference;

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

        $this->assertContainerBuilderHasParameter('gos_web_socket.client.storage.ttl');
        $this->assertContainerBuilderHasParameter('gos_web_socket.client.storage.prefix');
        $this->assertContainerBuilderNotHasService('gos_web_socket.pusher.amqp');
        $this->assertContainerBuilderNotHasService('gos_web_socket.pusher.wamp');
        $this->assertContainerBuilderNotHasService('gos_web_socket.pusher.amqp.push_handler');
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

    public function testContainerIsLoadedWithEnvVars(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('env(GOS_WEB_SOCKET_SERVER_HOST)', '127.0.0.1');
        $this->container->setParameter('env(GOS_WEB_SOCKET_SERVER_PORT)', 8080);

        $bundleConfig = [
            'server' => [
                'host' => '%env(GOS_WEB_SOCKET_SERVER_HOST)%',
                'port' => '%env(int:GOS_WEB_SOCKET_SERVER_PORT)%',
                'origin_check' => false,
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasParameter('gos_web_socket.server.port');
        $this->assertContainerBuilderHasParameter('gos_web_socket.server.host');
    }

    public function testContainerIsLoadedWithPubSubBundleIntegration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'MonologBundle' => MonologBundle::class,
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $bundleConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'router' => [],
            ],
        ];

        // Prepend config now to allow the prepend pass to work
        $this->container->prependExtensionConfig('gos_web_socket', $bundleConfig);

        // Also load the bundle config so it is passed to the extension load method
        $this->load($bundleConfig);

        $this->assertSame(
            [
                [
                    'routers' => [
                        'websocket' => [
                            'resources' => [],
                        ],
                    ],
                ],
            ],
            $this->container->getExtensionConfig('gos_pubsub_router'),
            'The GosPubSubRouterBundle should be configured when able.'
        );
    }

    public function testContainerIsLoadedWithPubSubBundleIntegrationAndConvertingLegacyConfigurationToNewerConfiguration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'MonologBundle' => MonologBundle::class,
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $bundleConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'router' => [
                    'resources' => [
                        'example.yaml',
                    ],
                ],
            ],
        ];

        // Prepend config now to allow the prepend pass to work
        $this->container->prependExtensionConfig('gos_web_socket', $bundleConfig);

        // Also load the bundle config so it is passed to the extension load method
        $this->load($bundleConfig);

        $this->assertSame(
            [
                [
                    'routers' => [
                        'websocket' => [
                            'resources' => [
                                [
                                    'resource' => 'example.yaml',
                                    'type' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $this->container->getExtensionConfig('gos_pubsub_router'),
            'The GosPubSubRouterBundle should be configured when able.'
        );
    }

    public function testContainerIsLoadedWithPubSubBundleIntegrationAndNewerConfiguration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'MonologBundle' => MonologBundle::class,
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);

        $bundleConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
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

        // Prepend config now to allow the prepend pass to work
        $this->container->prependExtensionConfig('gos_web_socket', $bundleConfig);

        // Also load the bundle config so it is passed to the extension load method
        $this->load($bundleConfig);

        $this->assertSame(
            [
                [
                    'routers' => [
                        'websocket' => [
                            'resources' => [
                                [
                                    'resource' => 'example.yaml',
                                    'type' => null,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $this->container->getExtensionConfig('gos_pubsub_router'),
            'The GosPubSubRouterBundle should be configured when able.'
        );
    }

    public function testContainerIsLoadedWithMonologBundleIntegration(): void
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'MonologBundle' => MonologBundle::class,
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->container->setParameter('kernel.debug', true);
        $this->load();

        $this->assertSame(
            [
                [
                    'channels' => [
                        'websocket',
                    ],
                    'handlers' => [
                        'websocket' => [
                            'type' => 'console',
                            'verbosity_levels' => [
                                'VERBOSITY_NORMAL' => Logger::DEBUG,
                            ],
                            'channels' => [
                                'type' => 'inclusive',
                                'elements' => [
                                    'websocket',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $this->container->getExtensionConfig('monolog'),
            'The MonologBundle should be configured when able.'
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

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.client.storage',
            'setStorageDriver',
            [new Reference('gos_web_socket.client.driver.in_memory')]
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

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'gos_web_socket.client.storage',
            'setStorageDriver',
            [new Reference('gos_web_socket.client.driver.symfony_cache')]
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
                        ],
                        [
                            'name' => 'pdo',
                            'type' => Configuration::PING_SERVICE_TYPE_PDO,
                        ],
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithTag('gos_web_socket.periodic_ping.doctrine.database_connection', 'gos_web_socket.periodic');
        $this->assertContainerBuilderHasServiceDefinitionWithTag('gos_web_socket.periodic_ping.pdo.pdo', 'gos_web_socket.periodic');
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

        $this->container->setParameter('env(GOS_WEB_SOCKET_WAMP_PUSHER_HOST)', '127.0.0.1');
        $this->container->setParameter('env(GOS_WEB_SOCKET_WAMP_PUSHER_PORT)', 1337);

        $bundleConfig = [
            'pushers' => [
                'wamp' => [
                    'enabled' => true,
                    'host' => '%env(GOS_WEB_SOCKET_WAMP_PUSHER_HOST)%',
                    'port' => '%env(int:GOS_WEB_SOCKET_WAMP_PUSHER_PORT)%',
                    'ssl' => false,
                    'origin' => null,
                ],
            ],
        ];

        $this->load($bundleConfig);

        $this->assertContainerBuilderHasService('gos_web_socket.pusher.wamp');
        $this->assertContainerBuilderHasService('gos_web_socket.pusher.wamp.connection_factory');

        $this->assertInstanceOf(WampConnectionFactory::class, $this->container->get('gos_web_socket.pusher.wamp.connection_factory'));
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
        $this->assertContainerBuilderHasService('gos_web_socket.pusher.amqp.connection_factory');

        $this->assertInstanceOf(AmqpConnectionFactory::class, $this->container->get('gos_web_socket.pusher.amqp.connection_factory'));
    }

    protected function getContainerExtensions(): array
    {
        return [
            new GosWebSocketExtension(),
        ];
    }
}
