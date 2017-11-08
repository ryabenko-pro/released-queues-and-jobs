<?php

namespace Released\JobsBundle\DependencyInjection;

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
        $rootNode = $treeBuilder->root('released_jobs');

        $types = $rootNode->children()
                ->scalarNode("template")->defaultValue("ReleasedJobsBundle::base.html.twig")->end()
                // TODO: EM must be just "default"
                ->scalarNode("em")->defaultValue("doctrine.orm.default_entity_manager")->end()
                ->arrayNode("types")->requiresAtLeastOneElement()->prototype('array')
                    ->children()
                        ->scalarNode("name")->isRequired()->end()
                        ->scalarNode("priority")->defaultValue(0)->end()
                        ->scalarNode("job_class")->isRequired()->end()
                        ->scalarNode("planning_interval")->defaultValue(null)->end()
                        ->scalarNode("process_class")->isRequired()->end()
                        ->scalarNode("packages_chunk")->defaultValue(10)->end();

        return $treeBuilder;
    }
}
