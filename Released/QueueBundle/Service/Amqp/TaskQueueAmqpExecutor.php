<?php

namespace Released\QueueBundle\Service\Amqp;

use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Exception\TaskAddException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\Logger\TaskLoggerAggregate;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


class TaskQueueAmqpExecutor implements TaskLoggerInterface
{

    /** @var ReleasedAmqpFactory */
    protected $factory;
    /** @var ContainerInterface */
    protected $container;
    /** @var ConfigQueuedTaskType[] */
    protected $types;
    /** @var LoggerInterface|null */
    protected $logger;

    function __construct(ReleasedAmqpFactory $factory, ContainerInterface $container, $types, LoggerInterface $logger = null)
    {
        $this->factory = $factory;
        $this->container = $container;
        $this->types = $this->fixTypes($types);
        $this->logger = $logger;
    }

    /**
     * @param string[]|null $types
     * @param string[]|null $noTypes
     * @param TaskLoggerInterface|null $logger
     */
    public function runTasks($types = null, $noTypes = null, TaskLoggerInterface $logger = null)
    {

        /** @var ConfigQueuedTaskType[] $selectedTypes */
        $selectedTypes = array_filter($this->types, function (ConfigQueuedTaskType $type) use ($types, $noTypes) {
            $matches = empty($types) || false !== array_search($type->getName(), (array)$types);
            $blocks = !empty($noTypes) && false !== array_search($type->getName(), (array)$noTypes);

            return $matches && !$blocks;
        });

        foreach ($selectedTypes as $type) {
            $consumer = $this->factory->getConsumer($type->getName());

            $consumer->setCallback(function (AMQPMessage $message) use ($type, $logger) {
                // Check for stop file and nack
                return $this->processMessage($type, $message, $logger);
            });

            $consumer->start();
        }
    }

    public function processMessage(ConfigQueuedTaskType $type, AMQPMessage $message, TaskLoggerInterface $logger = null): bool
    {
        $logger = is_null($logger) ? $this : new TaskLoggerAggregate([$this, $logger]);

        // Create task
        $class = $type->getClassName();
        if (!is_subclass_of($class, BaseTask::class)) {
            throw new TaskAddException("Task class '{$class}' must be subclass of " . BaseTask::class);
        }

        $payload = unserialize($message->getBody());

        /** @var BaseTask $task */
        $task = new $class($payload['data'] ?? []);

        // Execute task and nack if false
        if (!$task->execute($this->container, $logger)) {
            return false;
        }

        // Enqueue next tasks
        if (isset($payload['next'])) {
            foreach ($payload['next'] as $nextPayload) {
                $producer = $this->factory->getProducer($nextPayload['type']);

                $producer->publish(serialize($nextPayload));
            }
        }

        return true;
    }

    /** {@inheritdoc} */
    public function log(BaseTask $task, $message, $type = self::LOG_MESSAGE)
    {
        if (!is_null($this->logger)) {
            $this->logger->log($type, $message, ['task_type' => $task->getType()]);
        }
    }

    /**
     * @param $types
     * @return array
     */
    protected function fixTypes($types): array
    {
        $result = [];

        foreach ($types as $key => $type) {
            if ($type instanceof ConfigQueuedTaskType) {
                $result[$key] = $type;
            } else {
                $result[$key] = new ConfigQueuedTaskType($key, $type['class_name'], $type['priority'], $type['local'], $type['retry_limit']);
            }
        }

        return $result;
    }

}