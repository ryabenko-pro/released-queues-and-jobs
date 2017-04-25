<?php


namespace Released\QueueBundle\Service;


use Released\QueueBundle\Model\BaseTask;

interface TaskLoggerInterface
{

    const LOG_MESSAGE = "message";
    const LOG_NOTICE = "notice";
    const LOG_ERROR = "error";

    /**
     * Add log to
     *
     * @param BaseTask $task
     * @param string $message
     * @param string $type
     * @return
     */
    public function log(BaseTask $task, $message, $type = self::LOG_MESSAGE);

}