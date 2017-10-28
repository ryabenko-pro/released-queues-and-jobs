<?php


namespace Released\QueueBundle\DependencyInjection\Util;


class ConfigQueuedTaskType
{
    protected $name;
    protected $className;
    protected $priority;
    protected $isLocal;
    protected $retryLimit;

    function __construct($name, $className, $priority, $isLocal = false, $retry = 1)
    {
        $this->name = $name;
        $this->className = $className;
        $this->priority = $priority;
        $this->isLocal = $isLocal;
        $this->retryLimit = $retry;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return boolean
     */
    public function isLocal()
    {
        return $this->isLocal;
    }

    /**
     * @return int
     */
    public function getRetryLimit()
    {
        return $this->retryLimit;
    }
}