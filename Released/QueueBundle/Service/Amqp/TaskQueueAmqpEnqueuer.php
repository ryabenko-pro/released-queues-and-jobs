<?php

namespace Released\QueueBundle\Service\Amqp;

use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\EnqueuerInterface;

class TaskQueueAmqpEnqueuer implements EnqueuerInterface
{

    /** @var ReleasedAmqpFactory */
    protected $factory;
    /** @var ConfigQueuedTaskType[] */
    protected $types;

    function __construct(ReleasedAmqpFactory $factory, $types)
    {
        $this->factory = $factory;
        $this->types = $this->fixTypes($types);
    }

    /** {@inheritdoc} */
    public function enqueue(BaseTask $task)
    {
        // Get producer
        $producer = $this->factory->getProducer($this->getQueueType($task));

        $payload = $this->buildPayload($task);

        $message = serialize($payload);

        $producer->publish($message);
    }

    /** {@inheritdoc} */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {
        throw new BCBreakException('You must use {enqueue} method now.');
    }

    /**
     * @param BaseTask $task
     * @return array
     */
    protected function buildPayload(BaseTask $task): array
    {
        $payload = [
            'type' => $task->getType(),
            'data' => $task->getData(),
        ];

        if ($task->getNextTasks()) {
            $payload['next'] = [];

            foreach ($task->getNextTasks() as $next) {
                $payload['next'][] = $this->buildPayload($next);
            }
        }

        return $payload;
    }

    /**
     * @param BaseTask $task
     * @return ConfigQueuedTaskType
     */
    protected function getQueueType(BaseTask $task)
    {
        return $this->types[$task->getType()];
    }

    /**
     * TODO: duplicate from {@see TaskQueueAmqpExecutor}
     * @param $types
     * @return array
     */
    protected function fixTypes($types): array
    {
        $result = [];

        foreach ($types as $key => $type) {
            if ($type instanceof ConfigQueuedTaskType) {
                $result[$type->getName()] = $type;
            } else {
                $result[$key] = new ConfigQueuedTaskType($key, $type['class_name'], $type['priority'], $type['local'], $type['retry_limit']);
            }
        }

        return $result;
    }

}