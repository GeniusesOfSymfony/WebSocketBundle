<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class Configuration implements ConfigurationInterface
{
    const DEFAULT_TTL = 900;
    const DEFAULT_PREFIX = '';
    const DEFAULT_CLIENT_STORAGE_SERVICE = '@gos_web_socket.server.in_memory.client_storage.driver';
    const DEFAULT_FIREWALL = 'ws_firewall';
    const DEFAULT_ORIGIN_CHECKER = false;
    const DEFAULT_TOKEN_SEPARATOR = '/';

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
                            ->scalarNode('ttl')
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
            ->append($this->addAsseticNode())
            ->booleanNode('shared_config')
                ->defaultValue(true)
            ->end()
            ->arrayNode('server')
                ->children()
                    ->scalarNode('port')
                        ->example(1337)
                        ->cannotBeEmpty()
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
                    ->arrayNode('router')
                        ->children()
                            ->arrayNode('resources')
                                ->prototype('scalar')
                                    ->example('@GosNotificationBundle/Resources/config/pubsub/websocket/notification.yml')
                                ->end()
                            ->end()
                            ->arrayNode('context')
                                ->children()
                                    ->variableNode('tokenSeparator')
                                        ->example('/')
                                        ->defaultValue(static::DEFAULT_TOKEN_SEPARATOR)
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->append($this->addRpcNode())
            ->append($this->addTopicsNode())
            ->append($this->addPeriodicNode())
            ->append($this->addServersNode())
            ->arrayNode('origins')
                ->prototype('scalar')
                ->validate()
                    ->ifInArray(array('localhost', '127.0.0.1'))
                        ->thenInvalid('%s is added by default')
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
                    ->example('1337')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('ssl')
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
                    ->example('1337')
                    ->isRequired()
                    ->cannotBeEmpty()
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

        if (method_exists($node, 'setDeprecated')) {
            $node->setDeprecated('The child node "%node%" at path "%path%" is deprecated. Support for ZMQ will be removed.');
        }

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
                    ->example('1337')
                    ->isRequired()
                    ->cannotBeEmpty()
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
                ->scalarNode('read_timeout')
                    ->defaultValue(0)
                ->end()
                ->scalarNode('write_timeout')
                    ->defaultValue(0)
                ->end()
                ->scalarNode('connect_timeout')
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
