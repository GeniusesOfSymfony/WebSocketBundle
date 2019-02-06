<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class Configuration implements ConfigurationInterface
{
    private const DEFAULT_TTL = 900;
    private const DEFAULT_PREFIX = '';
    private const DEFAULT_CLIENT_STORAGE_SERVICE = '@gos_web_socket.server.in_memory.client_storage.driver';
    private const DEFAULT_FIREWALL = 'ws_firewall';
    private const DEFAULT_ORIGIN_CHECKER = false;
    private const DEFAULT_KEEPALIVE_PING = false;
    private const DEFAULT_KEEPALIVE_INTERVAL = 30;
    public const PING_SERVICE_TYPE_DOCTRINE = 'doctrine';
    public const PING_SERVICE_TYPE_PDO = 'pdo';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('gos_web_socket');

        if (method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $rootNode = $treeBuilder->root('gos_web_socket');
        }

        $rootNode->children()
            ->arrayNode('client')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('session_handler')
                        ->example('@session.handler.pdo')
                    ->end()
                    ->variableNode('firewall')
                        ->example('secured_area')
                        ->defaultValue(static::DEFAULT_FIREWALL)
                    ->end()
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('driver')
                                ->defaultValue(static::DEFAULT_CLIENT_STORAGE_SERVICE)
                                ->example('@gos_web_socket.server.in_memory.client_storage.driver')
                            ->end()
                            ->integerNode('ttl')
                                ->defaultValue(static::DEFAULT_TTL)
                                ->example(3600)
                            ->end()
                            ->scalarNode('prefix')
                                ->defaultValue(static::DEFAULT_PREFIX)
                                ->example('client')
                            ->end()
                            ->scalarNode('decorator')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->booleanNode('shared_config')
                ->defaultValue(true)
            ->end()
            ->arrayNode('server')
                ->children()
                    ->scalarNode('port')
                        ->example(1337)
                        ->isRequired()
                    ->end()
                    ->scalarNode('host')
                        ->example('127.0.0.1')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->booleanNode('origin_check')
                        ->defaultValue(static::DEFAULT_ORIGIN_CHECKER)
                        ->example(true)
                    ->end()
                    ->booleanNode('keepalive_ping')
                        ->defaultValue(static::DEFAULT_KEEPALIVE_PING)
                        ->example(true)
                        ->info('Flag indicating a keepalive ping should be enabled on the server')
                    ->end()
                    ->integerNode('keepalive_interval')
                        ->defaultValue(static::DEFAULT_KEEPALIVE_INTERVAL)
                        ->example(30)
                        ->info('The time in seconds between each keepalive ping')
                    ->end()
                    ->arrayNode('router')
                        ->children()
                            ->arrayNode('resources')
                                ->scalarPrototype()
                                    ->example('@GosNotificationBundle/Resources/config/pubsub/websocket/notification.yml')
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('origins')
                ->scalarPrototype()
                ->validate()
                    ->ifInArray(['localhost', '127.0.0.1'])
                        ->thenInvalid('%s is added by default')
                    ->end()
                ->end()
            ->end()
            ->arrayNode('ping')
                ->children()
                    ->arrayNode('services')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('name')
                                    ->info('The name of the service to ping')
                                ->end()
                                ->scalarNode('type')
                                    ->info('The type of the service to be pinged; valid options are "doctrine" and "pdo"')
                                    ->validate()
                                        ->ifNotInArray([self::PING_SERVICE_TYPE_DOCTRINE, self::PING_SERVICE_TYPE_PDO])
                                        ->thenInvalid('%s is not a supported service type')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('pushers')
                ->append($this->addZmqNode())
                ->append($this->addAmqpNode())
                ->append($this->addWampNode())
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }

    /**
     * @deprecated The `assetic` config node is deprecated
     */
    protected function addAsseticNode()
    {
        $builder = new TreeBuilder('assetic', 'boolean');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('assetic', 'boolean');
        }

        $node
            ->defaultTrue()
            ->end();

        if (method_exists($node, 'setDeprecated')) {
            $node->setDeprecated();
        }

        return $node;
    }

    /**
     * @deprecated The `rpc` config node is deprecated
     */
    protected function addRpcNode()
    {
        $builder = new TreeBuilder('rpc');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('rpc');
        }

        $node
            ->prototype('scalar')
                ->example('@gos.rpc_service')
            ->end();

        if (method_exists($node, 'setDeprecated')) {
            $node->setDeprecated('The child node "%node%" at path "%path%" is deprecated. Add the `gos_web_socket.rpc` tag to your service definitions instead.');
        }

        return $node;
    }

    /**
     * @deprecated The `topics` config node is deprecated
     */
    protected function addTopicsNode()
    {
        $builder = new TreeBuilder('topics');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('topics');
        }

        $node
            ->prototype('scalar')
                ->example('@gos.topic_service')
            ->end();

        if (method_exists($node, 'setDeprecated')) {
            $node->setDeprecated('The child node "%node%" at path "%path%" is deprecated. Add the `gos_web_socket.topic` tag to your service definitions instead.');
        }

        return $node;
    }

    /**
     * @deprecated The `periodic` config node is deprecated
     */
    protected function addPeriodicNode()
    {
        $builder = new TreeBuilder('periodic');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('periodic');
        }

        $node
            ->prototype('scalar')
                ->example('@gos.periodic_service')
            ->end();

        if (method_exists($node, 'setDeprecated')) {
            $node->setDeprecated('The child node "%node%" at path "%path%" is deprecated. Add the `gos_web_socket.periodic` tag to your service definitions instead.');
        }

        return $node;
    }

    /**
     * @deprecated The `servers` config node is deprecated
     */
    protected function addServersNode()
    {
        $builder = new TreeBuilder('servers');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('servers');
        }

        $node
            ->prototype('scalar')
                ->example('@gos.server_service')
            ->end();

        if (method_exists($node, 'setDeprecated')) {
            $node->setDeprecated('The child node "%node%" at path "%path%" is deprecated. Add the `gos_web_socket.server` tag to your service definitions instead.');
        }

        return $node;
    }

    protected function addWampNode()
    {
        $builder = new TreeBuilder('wamp');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('wamp');
        }

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('host')
                    ->example('127.0.0.1')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
                    ->example(1337)
                    ->isRequired()
                ->end()
                ->booleanNode('ssl')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('origin')
                    ->defaultValue(null)
                ->end()
            ->end();

        return $node;
    }

    protected function addZmqNode()
    {
        $builder = new TreeBuilder('zmq');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('zmq');
        }

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('default')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('host')
                    ->example('127.0.0.1')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
                    ->example(1337)
                    ->isRequired()
                ->end()
                ->booleanNode('persistent')
                    ->defaultTrue()
                ->end()
                ->enumNode('protocol')
                    ->defaultValue('tcp')
                    ->values(['tcp', 'ipc', 'inproc', 'pgm', 'epgm'])
                ->end()
                ->integerNode('linger')
                    ->defaultValue(-1)
                ->end()
            ->end();

        return $node;
    }

    protected function addAmqpNode()
    {
        $builder = new TreeBuilder('amqp');

        if (method_exists($builder, 'getRootNode')) {
            $node = $builder->getRootNode();
        } else {
            // BC layer for symfony/config 4.1 and older
            $node = $builder->root('amqp');
        }

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('default')
                    ->defaultValue(false)
                ->end()
                ->scalarNode('host')
                    ->example('127.0.0.1')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('port')
                    ->example(1337)
                    ->isRequired()
                ->end()
                ->scalarNode('login')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('password')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('vhost')
                    ->defaultValue('/')
                ->end()
                ->integerNode('read_timeout')
                    ->defaultValue(0)
                ->end()
                ->integerNode('write_timeout')
                    ->defaultValue(0)
                ->end()
                ->integerNode('connect_timeout')
                    ->defaultValue(0)
                ->end()
                ->scalarNode('queue_name')
                    ->defaultValue('gos_websocket')
                ->end()
                ->scalarNode('exchange_name')
                    ->defaultValue('gos_websocket_exchange')
                ->end()
            ->end();

        return $node;
    }
}
