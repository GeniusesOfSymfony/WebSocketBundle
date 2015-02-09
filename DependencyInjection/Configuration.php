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
            ->scalarNode('session_handler')->end()
            ->scalarNode('shared_config')->end()
            ->arrayNode('web_socket_server')
                ->children()
                    ->scalarNode('port')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->scalarNode('host')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                ->end()
            ->end()
            ->arrayNode('rpc')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('topic')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('periodic')
                ->prototype('scalar')
                ->end()
            ->end()
            ->arrayNode('server')
                ->prototype('scalar')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
