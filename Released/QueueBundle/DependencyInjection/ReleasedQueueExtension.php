<?php

namespace Released\QueueBundle\DependencyInjection;

use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class ReleasedQueueExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter("released.queue.task_types", $config['types']);

        $container->setParameter('released.queue.base_template', $config['template']);
        $container->setParameter('released.queue.entity_manager', $config['em']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

//        if ('prod' != $container->getParameter('kernel.environment')) {
//            $loader->load('services_dev.yml');
//        }
    }
}
