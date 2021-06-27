<?php declare(strict_types=1);

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultConfig(): void
    {
        $config = (new Processor())->processConfiguration(new Configuration(), []);

        self::assertEquals(self::getBundleDefaultConfig(), $config);
    }

    public function testConfigWithAServer(): void
    {
        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'ip_address_check' => false,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
            ],
        ];

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    public function testConfigWithAServerAndPubSubRouterWithoutArrayResources(): void
    {
        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'ip_address_check' => false,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
                'router' => [
                    'resources' => [
                        'example.yaml',
                    ],
                ],
            ],
        ];

        $normalizedExtraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'ip_address_check' => false,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
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

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $normalizedExtraConfig),
            $config
        );
    }

    public function testConfigWithAServerAndPubSubRouterWithArrayResources(): void
    {
        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'ip_address_check' => false,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
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

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    public function testConfigWithAllowedOriginsList(): void
    {
        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => true,
                'ip_address_check' => false,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
            ],
            'origins' => [
                'websocket-bundle.localhost',
            ],
        ];

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    public function testConfigWithInvalidOriginsList(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "gos_web_socket.origins.0": "localhost" is added by default');

        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => true,
                'ip_address_check' => false,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
            ],
            'origins' => [
                'localhost',
                'websocket-bundle.localhost',
            ],
        ];

        (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);
    }

    public function testConfigWithBlockedIpAddressList(): void
    {
        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'ip_address_check' => true,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
            ],
            'blocked_ip_addresses' => [
                '192.168.1.1',
            ],
        ];

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    public function testConfigWithPingServices(): void
    {
        $extraConfig = [
            'ping' => [
                'services' => [
                    [
                        'name' => 'doctrine_service',
                        'type' => Configuration::PING_SERVICE_TYPE_DOCTRINE,
                        'interval' => 30,
                    ],
                    [
                        'name' => 'pdo_service',
                        'type' => Configuration::PING_SERVICE_TYPE_PDO,
                        'interval' => 15,
                    ],
                ],
            ],
        ];

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    public function testConfigWithUnsupportedPingServiceType(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "no_support" is not allowed for path "gos_web_socket.ping.services.0.type". Permissible values: "doctrine", "pdo"');

        $extraConfig = [
            'ping' => [
                'services' => [
                    [
                        'name' => 'no_support_service',
                        'type' => 'no_support',
                    ],
                ],
            ],
        ];

        (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);
    }

    public function testConfigWithInvalidPingInterval(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value 0 is too small for path "gos_web_socket.ping.services.0.interval".');

        $extraConfig = [
            'ping' => [
                'services' => [
                    [
                        'name' => 'doctrine_service',
                        'type' => Configuration::PING_SERVICE_TYPE_DOCTRINE,
                        'interval' => 0,
                    ],
                ],
            ],
        ];

        (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);
    }

    /**
     * @group legacy
     */
    public function testConfigWithPushers(): void
    {
        $extraConfig = [
            'pushers' => [
                'wamp' => [
                    'enabled' => false,
                    'host' => '127.0.0.1',
                    'port' => 1337,
                    'ssl' => false,
                    'origin' => null,
                ],
                'amqp' => [
                    'enabled' => false,
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

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    /**
     * @group legacy
     */
    public function testConfigWithWebsocketClient(): void
    {
        $extraConfig = [
            'websocket_client' => [
                'enabled' => false,
                'host' => '127.0.0.1',
                'port' => 1337,
                'ssl' => false,
                'origin' => null,
            ],
        ];

        $config = (new Processor())->processConfiguration(new Configuration(), [$extraConfig]);

        self::assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    protected static function getBundleDefaultConfig(): array
    {
        return [
            'client' => [
                'firewall' => 'ws_firewall',
                'storage' => [
                    'driver' => 'gos_web_socket.client.driver.in_memory',
                    'ttl' => 900,
                    'prefix' => '',
                ],
            ],
            'shared_config' => true,
            'server' => [
                'origin_check' => false,
                'ip_address_check' => false,
                'keepalive_ping' => false,
                'keepalive_interval' => 30,
            ],
            'origins' => [],
            'blocked_ip_addresses' => [],
            'websocket_client' => [
                'enabled' => false,
                'ssl' => false,
                'origin' => false,
            ],
        ];
    }
}
