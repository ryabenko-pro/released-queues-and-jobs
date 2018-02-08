<?php

namespace Released\QueueBundle\Service\Amqp;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\EnqueuerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskQueueAmqpEnqueuer implements EnqueuerInterface
{

    /** @var ReleasedAmqpFactory */
    protected $factory;

    function __construct(ReleasedAmqpFactory $factory)
    {
        $this->factory = $factory;
    }

    /** {@inheritdoc} */
    public function enqueue(BaseTask $task)
    {
        // Get producer
        $producer = $this->factory->getProducer($task->getType());

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


}