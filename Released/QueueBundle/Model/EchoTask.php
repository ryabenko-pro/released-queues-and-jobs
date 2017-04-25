<?php


namespace Released\QueueBundle\Model;


use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EchoTask extends BaseTask
{

    /**
     * @inheritdoc
     */
    public function execute(ContainerInterface $container, TaskLoggerInterface $logger)
    {
        print_r($this->data);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return 'echo';
    }
}