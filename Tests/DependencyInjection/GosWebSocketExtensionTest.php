<?php

namespace Gos\Bundle\WebSocketBundle\Tests;

use Gos\Bundle\PubSubRouterBundle\GosPubSubRouterBundle;
use Gos\Bundle\WebSocketBundle\DependencyInjection\GosWebSocketExtension;
use Gos\Bundle\WebSocketBundle\GosWebSocketBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Monolog\Logger;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;

class ConfigurationTest extends AbstractExtensionTestCase
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
            'The Monolog should be configured when able.'
        );
    }

    protected function getContainerExtensions()
    {
        return [
            new GosWebSocketExtension(),
        ];
    }
}
