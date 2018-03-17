<?php


namespace Released\QueueBundle\Service;


use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Model\BaseTask;


/**
 * This class is a decorator to be able to replace implementation based on config transport
 */
class TaskQueueService implements EnqueuerInterface
{
    /** @var EnqueuerInterface */
    protected $enqueuer;

    public function __construct(EnqueuerInterface $enqueuer)
    {
        $this->enqueuer = $enqueuer;
    }

    /**
     * This method redirects calls to `enqueue` method, unless deprecated $parent parameter is provided
     * {@inheritdoc}
     */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {
        if (!is_null($parent)) {
            throw new BCBreakException('You can`t use $parent parameter anymore. Please add $task to $parent as "next" task and use {enqueue} method.');
        }

        return $this->enqueuer->enqueue($task);
    }

    /** {@inheritdoc} */
    public function enqueue(BaseTask $task)
    {
        return $this->enqueuer->enqueue($task);
    }

    /** {@inheritdoc} */
    public function retry(BaseTask $task)
    {
        return $this->enqueuer->enqueue($task);
    }
}