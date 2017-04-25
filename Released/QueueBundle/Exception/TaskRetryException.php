<?php


namespace Released\QueueBundle\Exception;


use Exception;

class TaskRetryException extends \RuntimeException implements QueueTaskException
{

    protected $timeout;

    /**
     * @param int $timeout Timeout in seconds to retry the task
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($timeout = null, $message = "", $code = 0, Exception $previous = null)
    {
        $this->timeout = $timeout;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return mixed
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

}