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
                ->children()
                    ->scalarNode('session_handler')
                        ->example('@session.handler.pdo')
                    ->end()
                    ->variableNode('firewall')
                        ->example('secured_area')
                    ->end()
                    ->arrayNode('storage')
                        ->children()
                            ->scalarNode('driver')->end()
                            ->scalarNode('decorator')->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('shared_config')->end()
            ->arrayNode('server')
                ->children()
                    ->scalarNode('port')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->scalarNode('host')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->booleanNode('origin_check')
                        ->isRequired()
                        ->defaultTrue()
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
