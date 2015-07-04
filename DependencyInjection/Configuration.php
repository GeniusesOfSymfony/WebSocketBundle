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
    const DEFAULT_CLIENT_STORAGE_SERVICE = '@gos_web_socket.server.in_memory.client_storage.driver';
    const DEFAULT_FIREWALL = 'ws_firewall';
    const DEFAULT_ORIGIN_CHECKER = false;

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('gos_web_socket');

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
                            ->scalarNode('decorator')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->booleanNode('assetic')
                ->defaultValue(true)
            ->end()
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
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('rpc')
                ->prototype('scalar')
                    ->example('@gos.rpc_service')
                ->end()
            ->end()
            ->arrayNode('topics')
                ->prototype('scalar')
                    ->example('@gos.topic_service')
                ->end()
            ->end()
            ->arrayNode('periodic')
                ->prototype('scalar')
                    ->example('@gos.periodic_service')
                ->end()
            ->end()
            ->arrayNode('servers')
                ->prototype('scalar')
                    ->example('gos.server_service')
                ->end()
            ->end()
            ->arrayNode('origins')
                ->prototype('scalar')
                ->validate()
                    ->ifInArray(array('localhost', '127.0.0.1'))
                        ->thenInvalid('%s is added by default')
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
