<?php

namespace Released\QueueBundle;

use Released\QueueBundle\Command as Cmd;
use Released\QueueBundle\DependencyInjection\Pass\QueueServicePass;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ReleasedQueueBundle extends Bundle
{

    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new QueueServicePass());
    }

    /** {@inheritdoc} */
    public function registerCommands(Application $application)
    {
        $application->add(new Cmd\ReleasedQueueGcCommand());
        $application->add(new Cmd\ReleasedQueueExecuteTaskAmqpCommand());
        $application->add(new Cmd\ReleasedQueueCreateTaskCommand());
        $application->add(new Cmd\ReleasedQueueExecuteTaskDbCommand());
    }
}
