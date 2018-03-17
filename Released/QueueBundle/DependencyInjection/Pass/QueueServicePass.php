<?php


namespace Released\QueueBundle\DependencyInjection\Pass;


use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class QueueServicePass implements CompilerPassInterface
{

    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        $transport = $this->getTransport($container->getParameter('released.queue.transport'));

        $container->getDefinition('released.queue.task_queue.service')->setArguments([new Reference($transport)]);
    }

    /**
     * @param string $transport
     * @return string
     */
    private function getTransport($transport)
    {
        switch (mb_strtolower($transport)) {
            case 'db':
                return 'released.queue.task_queue.service_database';
            case 'amqp':
                return 'released.queue.task_queue.service_amqp';
            case 'mixed':
                return 'released.queue.task_queue.service_mixed';
            case 'inline':
                return 'released.queue.task_queue.service_inline';
            default:
                throw new RuntimeException("{$transport} is not yet implemented");
        }
    }

}