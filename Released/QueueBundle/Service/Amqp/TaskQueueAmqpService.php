<?php

namespace Released\QueueBundle\Service\Amqp;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\EnqueuerInterface;

class TaskQueueAmqpService implements EnqueuerInterface
{

    /** @var Producer */
    protected $amqp;

    /** {@inheritdoc} */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {

    }
}