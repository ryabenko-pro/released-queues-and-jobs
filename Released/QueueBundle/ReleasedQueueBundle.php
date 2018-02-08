<?php

namespace Released\QueueBundle;

use Released\QueueBundle\DependencyInjection\Pass\QueueServicePass;
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

}
