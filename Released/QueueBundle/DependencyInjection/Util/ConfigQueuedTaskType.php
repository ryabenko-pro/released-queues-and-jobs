<?php


namespace Released\QueueBundle\DependencyInjection\Util;


class ConfigQueuedTaskType
{
    protected $name;
    protected $className;
    protected $priority;

    function __construct($name, $className, $priority)
    {
        $this->name = $name;
        $this->className = $className;
        $this->priority = $priority;
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

}