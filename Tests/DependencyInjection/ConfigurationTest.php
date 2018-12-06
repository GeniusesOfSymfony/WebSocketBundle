<?php

namespace Gos\Bundle\WebSocketBundle\Tests\DependencyInjection;

use Gos\Bundle\WebSocketBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testDefaultConfig()
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), []);

        $this->assertEquals(self::getBundleDefaultConfig(), $config);
    }

    public function testConfigWithAServer()
    {
        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
            ],
        ];

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [$extraConfig]);

        $this->assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    public function testConfigWithAServerAndPubSubRouter()
    {
        $extraConfig = [
            'server' => [
                'host' => '127.0.0.1',
                'port' => 8080,
                'origin_check' => false,
                'router' => [
                    'resources' => [
                        'example.yaml',
                    ],
                    'context' => [
                        'tokenSeparator' => '/',
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [$extraConfig]);

        $this->assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    public function testConfigWithPingServices()
    {
        $extraConfig = [
            'ping' => [
                'services' => [
                    [
                        'name' => 'doctrine_service',
                        'type' => Configuration::PING_SERVICE_TYPE_DOCTRINE,
                    ],
                    [
                        'name' => 'pdo_service',
                        'type' => Configuration::PING_SERVICE_TYPE_PDO,
                    ],
                ],
            ],
        ];

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [$extraConfig]);

        $this->assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @expectedExceptionMessage  Invalid configuration for path "gos_web_socket.ping.services.0.type": "no_support" is not a supported service type
     */
    public function testConfigWithUnsupportedPingServiceType()
    {
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

        $processor = new Processor();
        $processor->processConfiguration(new Configuration(), [$extraConfig]);
    }

    public function testConfigWithPushers()
    {
        $extraConfig = [
            'pushers' => [
                'wamp' => [
                    'host' => '127.0.0.1',
                    'port' => 1337,
                    'ssl' => false,
                    'origin' => null,
                ],
                'zmq' => [
                    'default' => false,
                    'host' => '127.0.0.1',
                    'port' => 1337,
                    'persistent' => true,
                    'protocol' => 'tcp',
                    'linger' => -1,
                ],
                'amqp' => [
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

        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [$extraConfig]);

        $this->assertEquals(
            array_merge(self::getBundleDefaultConfig(), $extraConfig),
            $config
        );
    }

    protected static function getBundleDefaultConfig()
    {
        return [
            'client' => [
                'firewall' => 'ws_firewall',
                'storage' => [
                    'driver' => '@gos_web_socket.server.in_memory.client_storage.driver',
                    'ttl' => 900,
                    'prefix' => ''
                ],
            ],
            'shared_config' => true,
            'origins' => [],
        ];
    }
}
