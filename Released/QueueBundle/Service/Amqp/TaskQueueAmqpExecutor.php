<?php

namespace Released\QueueBundle\Service\Amqp;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\EnqueuerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskQueueAmqpExecutor
{

    /** @var ContainerInterface */
    protected $container;
    /** @var ConfigQueuedTaskType[] */
    protected $types;
    /** @var null|string */
    protected $serverId;

    /**
     * @param ContainerInterface $container
     * @param ConfigQueuedTaskType[] $types
     * @param string|null $serverId ID of the server to run local tasks
     */
    function __construct($container, $types, $serverId = null)
    {
        $this->container = $container;
        $this->types = $types;
        $this->serverId = $serverId;
    }



}