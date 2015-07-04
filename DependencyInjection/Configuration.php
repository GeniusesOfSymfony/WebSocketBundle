<?php

namespace Gos\Bundle\WebSocketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Johann Saunier <johann_27@hotmail.fr>
 */
class Configuration implements ConfigurationInterface
{
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
                        ->defaultValue('ws_firewall')
                    ->end()
                    ->arrayNode('storage')
                        ->addDefaultsIfNotSet()
                        ->children()
                            ->scalarNode('driver')
                                ->defaultValue('@gos_web_socket.server.in_memory.client_storage.driver')
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
                        ->defaultValue(false)
                    ->end()
                    ->arrayNode('router')
                        ->children()
                            ->arrayNode('resources')
                                ->prototype('scalar')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('rpc')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('topics')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('periodic')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('servers')
                ->prototype('scalar')
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
