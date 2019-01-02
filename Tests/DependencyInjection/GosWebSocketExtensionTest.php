<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\DBAL\Connection;
use Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle;
use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
use Gos\Bundle\WebSocketBundle\GosWebSocketBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Monolog\Logger;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class GosWebSocketExtensionTest extends AbstractExtensionTestCase
{
    public function testContainerIsLoadedWithDefaultConfiguration()
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $this->load();

        $this->assertContainerBuilderHasParameter('web_socket_server.client_storage.ttl');
        $this->assertContainerBuilderHasParameter('web_socket_server.client_storage.prefix');
    }

    public function testContainerIsLoadedWithTwigBundleIntegration()
    {
        $this->container->setParameter(
            'kernel.bundles',
            [
                'TwigBundle' => TwigBundle::class,
                'GosPubSubRouterBundle' => GosPubSubRouterBundle::class,
                'GosWebSocketBundle' => GosWebSocketBundle::class,
            ]
        );

        $bundleConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
            ],
        ];

        // Prepend config now to allow the prepend pass to work
        $this->container->prependExtensionConfig('gos_web_socket', $bundleConfig);

        // Also load the bundle config so it is passed to the extension load method
        $this->load($bundleConfig);

        $this->assertContainerBuilderHasParameter('web_socket_server.port');
        $this->assertContainerBuilderHasParameter('web_socket_server.host');

        $this->assertSame(
            [
                [
                    'globals' => [
                        'gos_web_socket_server_host' => '127.0.0.1',
                        'gos_web_socket_server_port' => 8080,
                    ],
                ],
            ],
            $this->container->getExtensionConfig('twig'),
            'The TwigBundle should be configured when able.'
        );
    }

    public function testContainerIsLoadedWithMonologBundleIntegration()
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

    public function testContainerIsLoadedWithOriginsConfigured()
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

        $originRegistryDefinition = $this->container->getDefinition('gos_web_socket.origins.registry');

        $this->assertCount(
            1,
            $originRegistryDefinition->getMethodCalls(),
            'The origins should be added to the `gos_web_socket.origins.registry` service.'
        );
    }

    public function testContainerIsLoadedWithClientConfiguredWithoutCacheDecorator()
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
                        'driver' => 'gos_web_socket.server.in_memory.client_storage.driver',
                        'ttl' => 900,
                        'prefix' => '',
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter('gos_web_socket.firewall');
        $this->assertContainerBuilderHasAlias('gos_web_socket.session_handler');

        $serverBuilderDefinition = $this->container->getDefinition('gos_web_socket.ws.server_builder');

        $this->assertTrue(
            $serverBuilderDefinition->hasMethodCall('setSessionHandler'),
            'The session handler should be added to the `gos_web_socket.ws.server_builder` service.'
        );

        $clientStorageDefinition = $this->container->getDefinition('gos_web_socket.client_storage');
        $clientStorageMethodCalls = $clientStorageDefinition->getMethodCalls();

        $this->assertTrue(
            $clientStorageDefinition->hasMethodCall('setStorageDriver'),
            'The storage driver should be added to the `gos_web_socket.client_storage` service.'
        );

        /** @var Reference $reference */
        $reference = $clientStorageMethodCalls[1][1][0];

        $this->assertSame(
            'gos_web_socket.server.in_memory.client_storage.driver',
            (string) $reference,
            'The storage driver should be the configured driver from the `client.storage.driver` node.'
        );
    }

    public function testContainerIsLoadedWithClientConfiguredWithCacheDecorator()
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
                        'driver' => 'gos_web_socket.server.in_memory.client_storage.driver',
                        'ttl' => 900,
                        'prefix' => '',
                        'decorator' => 'gos_web_socket.client_storage.symfony.decorator',
                    ],
                ],
            ]
        );

        $this->assertContainerBuilderHasParameter('gos_web_socket.firewall');
        $this->assertContainerBuilderHasAlias('gos_web_socket.session_handler');

        $serverBuilderDefinition = $this->container->getDefinition('gos_web_socket.ws.server_builder');

        $this->assertTrue(
            $serverBuilderDefinition->hasMethodCall('setSessionHandler'),
            'The session handler should be added to the `gos_web_socket.ws.server_builder` service.'
        );

        $clientStorageDefinition = $this->container->getDefinition('gos_web_socket.client_storage');
        $clientStorageMethodCalls = $clientStorageDefinition->getMethodCalls();

        $this->assertTrue(
            $clientStorageDefinition->hasMethodCall('setStorageDriver'),
            'The storage driver should be added to the `gos_web_socket.client_storage` service.'
        );

        /** @var Reference $reference */
        $reference = $clientStorageMethodCalls[1][1][0];

        $this->assertSame(
            'gos_web_socket.client_storage.symfony.decorator',
            (string) $reference,
            'The storage driver should be the configured driver from the `client.storage.decorator` node.'
        );
    }

    public function testContainerIsLoadedWithPingServicesConfigured()
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

        $doctrineDatabaseConnectionDefinition = new Definition(Connection::class);
        $this->container->setDefinition('database_connection', $doctrineDatabaseConnectionDefinition);

        $pdoDefinition = new Definition(\PDO::class);
        $this->container->setDefinition('pdo', $pdoDefinition);

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

        $this->assertContainerBuilderHasService('gos_web_socket.periodic_ping.doctrine.database_connection');
        $this->assertContainerBuilderHasService('gos_web_socket.periodic_ping.pdo.pdo');
    }

    public function testContainerIsLoadedWithWampPusherConfigured()
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

        $this->assertContainerBuilderHasService('gos_web_socket.wamp.pusher.client');

        $pusherDef = $this->container->getDefinition('gos_web_socket.wamp.pusher');

        $this->assertCount(
            1,
            $pusherDef->getArguments()
        );
    }

    /**
     * @requires extension amqp
     */
    public function testContainerIsLoadedWithAmqpPusherConfigured()
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
                    'default' => false,
                    'host' => '127.0.0.1',
                    'port' => 1337,
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

        $this->assertContainerBuilderHasService('gos_web_socket.amqp.pusher.connection');
        $this->assertContainerBuilderHasService('gos_web_socket.amqp.pusher.exchange');
        $this->assertContainerBuilderHasService('gos_web_socket.amqp.pusher.queue');

        $pusherDef = $this->container->getDefinition('gos_web_socket.amqp.pusher');

        $this->assertCount(
            2,
            $pusherDef->getArguments()
        );
    }

    protected function getContainerExtensions()
    {
        return [
            new GosWebSocketExtension(),
        ];
    }
}
