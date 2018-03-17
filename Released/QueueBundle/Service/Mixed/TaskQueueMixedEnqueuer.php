<?php

namespace Released\QueueBundle\Service\Mixed;


use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpEnqueuer;
use Released\QueueBundle\Service\Db\TaskQueueDbService;
use Released\QueueBundle\Service\EnqueuerInterface;

class TaskQueueMixedEnqueuer implements EnqueuerInterface
{

    /** @var TaskQueueDbService */
    protected $dbEnqueuer;
    /** @var TaskQueueAmqpEnqueuer */
    protected $amqpEnqueuer;

    public function __construct(TaskQueueDbService $dbEnqueuer, TaskQueueAmqpEnqueuer $amqpEnqueuer)
    {
        $this->dbEnqueuer = $dbEnqueuer;
        $this->amqpEnqueuer = $amqpEnqueuer;
    }

    /** {@inheritdoc} */
    public function enqueue(BaseTask $task)
    {
        $this->dbEnqueuer->enqueue($task);
        $this->amqpEnqueuer->enqueue($task);
    }

    /** {@inheritdoc} */
    public function retry(BaseTask $task)
    {
        $this->dbEnqueuer->retry($task);
        $this->amqpEnqueuer->retry($task);
    }

    /** {@inheritdoc} */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {
        throw new BCBreakException('You must use {enqueue} method now.');
    }
}