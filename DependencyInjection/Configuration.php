<?php

namespace Youshido\AdminBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('y_admin');

        $rootNode
            ->children()
                ->scalarNode('name')->end()
                ->arrayNode('modules')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('type')->end()
                            ->scalarNode('icon')->end()
                            ->scalarNode('entity')->end()
                            ->scalarNode('link')->end()
                            ->scalarNode('title')->end()
                            ->scalarNode('group')->end()
                            ->scalarNode('where')->end()
                            ->scalarNode('security')->end()
                            ->booleanNode('private')->end()
                            ->booleanNode('nested')->end()
                            ->arrayNode('tabs')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('title')->end()
                                        ->scalarNode('template')->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->variableNode('handlers')
                            ->end()
                            ->arrayNode('columns')
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('title')->end()
                                        ->scalarNode('entity')->end()
                                        ->booleanNode('required')
                                            ->defaultTrue()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('actions')
                                ->prototype('array')
                                    ->children()
                                        ->arrayNode('show')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->arrayNode('hide')
                                            ->prototype('scalar')->end()
                                        ->end()
                                        ->scalarNode('title')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}
