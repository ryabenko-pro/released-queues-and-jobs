<?php


namespace Released\QueueBundle\Service;


use Released\QueueBundle\Model\BaseTask;

interface EnqueuerInterface
{

    /**
     * @deprecated If you need to add task with dependency add dependant task as "next" to parent
     * @see EnqueuerInterface::enqueue
     * @see BaseTask::addNextTask
     * Executes task. If $parent is present, do not execute $task until parent not finished
     *
     * @param BaseTask $task
     * @param BaseTask $parent
     */
    public function addTask(BaseTask $task, BaseTask $parent = null);

    /**
     * Enqueue task for execution with configured transport
     *
     * @param BaseTask $task
     * @return mixed
     */
    public function enqueue(BaseTask $task);

}