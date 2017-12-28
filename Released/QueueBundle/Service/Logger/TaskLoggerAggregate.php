<?php

namespace Released\QueueBundle\Service\Logger;


use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\TaskLoggerInterface;

class TaskLoggerAggregate implements TaskLoggerInterface
{
    /**
     * @var TaskLoggerInterface[]
     */
    protected $loggers = [];

    /**
     * @var TaskLoggerInterface[] $loggers
     */
    public function __construct($loggers = null)
    {
        if (is_array($loggers)) {
            foreach ($loggers as $logger) {
                $this->addLogger($logger);
            }
        }
    }

    public function addLogger(TaskLoggerInterface $logger)
    {
        $this->loggers[] = $logger;
    }

    /** {@inheritdoc} */
    public function log(BaseTask $task, $message, $type = self::LOG_MESSAGE)
    {
        foreach ($this->loggers as $logger) {
            $logger->log($task, $message, $type);
        }
    }
}