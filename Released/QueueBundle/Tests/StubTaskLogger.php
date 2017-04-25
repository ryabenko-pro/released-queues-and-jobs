<?php


namespace Released\QueueBundle\Tests;


use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\TaskLoggerInterface;

class StubTaskLogger implements TaskLoggerInterface
{

    public $logs = [];

    /**
     * @inheritdoc
     */
    public function log(BaseTask $task, $message, $type = self::LOG_MESSAGE)
    {
        $this->logs[] = sprintf('%s [%s]: %s', date('Y-m-d H:i:s'), $type, $message);
    }
}