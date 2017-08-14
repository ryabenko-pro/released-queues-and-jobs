<?php


namespace Released\QueueBundle\Tests;


use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StubTask extends BaseTask
{

    /**
     * @inheritdoc
     */
    public function execute(ContainerInterface $container, TaskLoggerInterface $logger) { }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return 'stub';
    }

    static public function getConfigType($isLocal = false)
    {
        return new ConfigQueuedTaskType('Stub task', __CLASS__, 5, $isLocal);
    }

}