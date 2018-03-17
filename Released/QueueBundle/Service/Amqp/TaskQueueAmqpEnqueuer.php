<?php

namespace Released\QueueBundle\Service\Amqp;

use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\EnqueuerInterface;

class TaskQueueAmqpEnqueuer implements EnqueuerInterface
{
    const PAYLOAD_TYPE = 'type';
    const PAYLOAD_DATA = 'data';
    const PAYLOAD_NEXT = 'next';
    const PAYLOAD_RETRY = 'retry';
    const PAYLOAD_TASK_ID = 'task_id';

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

        $message = MessageUtil::serialize($payload);

        $producer->publish($message);
    }

    /** {@inheritdoc} */
    public function retry(BaseTask $task)
    {
        $task->incRetries();

        $this->enqueue($task);
    }

    /**
     * @param BaseTask $task
     * @return array
     */
    protected function buildPayload(BaseTask $task): array
    {
        $payload = [];

        if (!is_null($task->getEntity())) {
            $payload[self::PAYLOAD_TASK_ID] = $task->getEntity()->getId();
        }

        $payload[self::PAYLOAD_TYPE] = $task->getType();
        $payload[self::PAYLOAD_DATA] = $task->getData();

        if ($task->getRetries() > 0) {
            $payload[self::PAYLOAD_RETRY] = $task->getRetries();
        }

        if ($task->getNextTasks()) {
            $payload[self::PAYLOAD_NEXT] = [];

            foreach ($task->getNextTasks() as $next) {
                $payload[self::PAYLOAD_NEXT][] = $this->buildPayload($next);
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

    /** {@inheritdoc} */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {
        throw new BCBreakException('You must use {enqueue} method now.');
    }
}