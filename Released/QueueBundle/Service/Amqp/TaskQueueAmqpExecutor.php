<?php

namespace Released\QueueBundle\Service\Amqp;

use Monolog\Logger;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Exception\TaskAddException;
use Released\QueueBundle\Exception\TaskExecutionException;
use Released\QueueBundle\Exception\TaskRetryException;
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

    protected $messagesLimit = null;
    protected $memoryLimit = null;

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

        $selectedTypes = array_values($selectedTypes);

        $consumer = $this->factory->getConsumer($selectedTypes);

        if (!is_null($this->memoryLimit)) {
            $consumer->setMemoryLimit($this->memoryLimit);
        }

        $consumer->setCallback(function (AMQPMessage $message) use ($logger) {
            // Check for stop file and nack
            $this->processMessage($message, $logger);
        });

        $consumer->start($this->messagesLimit);
    }

    /**
     * @param AMQPMessage $message
     * @param TaskLoggerInterface|null $logger
     *
     * @return void This function does not return anything anymore. If task failed it is restarted manually from callback if needed.
     */
    public function processMessage(AMQPMessage $message, TaskLoggerInterface $logger = null)
    {
        $payload = MessageUtil::unserialize($message->getBody());

        $type = $this->types[$payload['type']];

        $logger = is_null($logger) ? $this : new TaskLoggerAggregate([$this, $logger]);

        // Create task
        $class = $type->getClassName();
        if (!is_subclass_of($class, BaseTask::class)) {
            throw new TaskAddException("Task class '{$class}' must be subclass of " . BaseTask::class);
        }

        /** @var BaseTask $task */
        $task = new $class($payload['data'] ?? []);

        // Execute task and nack if false
        try {
            if ($task->execute($this->container, $logger)) {
                // Enqueue next tasks
                if (isset($payload['next'])) {
                    foreach ($payload['next'] as $nextPayload) {
                        $producer = $this->factory->getProducer($this->types[$nextPayload['type']]);

                        $producer->publish(MessageUtil::serialize($nextPayload));
                    }
                }
            } else {
                $this->retryTask($payload);
            }
        } catch (TaskRetryException $exception) {
            $this->retryTask($payload, true);
        } catch (\Exception $exception) {
            // TODO: catch and log exception
            $this->retryTask($payload);
        }
    }

    /** {@inheritdoc} */
    public function log(BaseTask $task, $message, $type = Logger::INFO)
    {
        $type = $this->getLogLevel($type);

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

    /**
     * @param null $messagesLimit
     * @return self
     */
    public function setMessagesLimit($messagesLimit)
    {
        $this->messagesLimit = $messagesLimit;
        return $this;
    }

    /**
     * @param null $memoryLimit
     * @return self
     */
    public function setMemoryLimit($memoryLimit)
    {
        $this->memoryLimit = $memoryLimit;
        return $this;
    }

    private function getLogLevel($type)
    {
        if (is_numeric($type)) {
            return $type;
        }

        switch ($type) {
            case self::LOG_ERROR:
                return Logger::ERROR;
            case self::LOG_MESSAGE:
                return Logger::INFO;
            case self::LOG_NOTICE:
                return Logger::DEBUG;

            default:
                return Logger::INFO;
        }
    }

    /**
     * @param array $payload
     * @param bool $force Force requeue task
     */
    protected function retryTask($payload, $force = false)
    {
        $type = $this->types[$payload['type']];

        $payload['retry'] = isset($payload['retry']) ? $payload['retry'] + 1 : 1;

        if ($force || $payload['retry'] <= $type->getRetryLimit()) {
            $producer = $this->factory->getProducer($type);
            $producer->publish(MessageUtil::serialize($payload));
        }
    }
}