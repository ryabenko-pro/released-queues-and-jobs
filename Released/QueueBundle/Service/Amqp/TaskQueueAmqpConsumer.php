<?php

namespace Released\QueueBundle\Service\Amqp;

use PhpAmqpLib\Message\AMQPMessage;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Service\Db\TaskQueueDbService;
use Released\QueueBundle\Service\TaskLoggerInterface;


class TaskQueueAmqpConsumer
{

    /** @var ReleasedAmqpFactory */
    private $factory;
    /** @var TaskQueueAmqpExecutor */
    protected $executor;
    /** @var TaskQueueDbService */
    protected $dbService;
    /** @var ConfigQueuedTaskType[] */
    private $types;

    protected $messagesLimit = null;
    protected $memoryLimit = null;

    function __construct(ReleasedAmqpFactory $factory, TaskQueueAmqpExecutor $executor, TaskQueueDbService $dbService, $types) {
        $this->factory = $factory;
        $this->executor = $executor;
        $this->dbService = $dbService;
        $this->types = $this->fixTypes($types);
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
     */
    public function processMessage(AMQPMessage $message, TaskLoggerInterface $logger = null)
    {
        $payload = MessageUtil::unserialize($message->getBody());

        if (isset($payload['task_id'])) {
            $this->dbService->runTaskById($payload['task_id']);
        } else {
            $this->executor->processMessage($message, $logger);
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
}