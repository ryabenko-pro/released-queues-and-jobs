<?php

namespace Released\QueueBundle\DependencyInjection;

use Released\QueueBundle\Entity\QueuedTask;
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
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('released_queue');

        $rootNode->children()
            ->scalarNode("template")->defaultValue("ReleasedQueueBundle::base.html.twig")->end()
            ->scalarNode("server_id")->end()
            ->scalarNode("transport")->defaultValue("db")->end()
            ->scalarNode("em")->defaultValue("doctrine.orm.default_entity_manager")->end()
            ->arrayNode('amqp')->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode("exchange_prefix")->defaultValue("released.")->end()
                ->end()
            ->end()
            ->arrayNode("types")->requiresAtLeastOneElement()->prototype('array')
            ->children()
                ->scalarNode("name")->isRequired()->end()
                ->scalarNode("priority")->defaultValue(QueuedTask::PRIORITY_MEDIUM)->end()
                ->scalarNode("retry_limit")->defaultValue(1)->end()
                ->scalarNode("class_name")->isRequired()->end()
                ->scalarNode("local")->defaultValue(false)->end()
            ->end();


        return $treeBuilder;
    }
}
