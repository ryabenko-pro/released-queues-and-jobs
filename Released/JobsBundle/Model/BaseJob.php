<?php

namespace Released\JobsBundle\Model;


use Released\JobsBundle\Entity\Job;
use Released\JobsBundle\Exception\ReleasedJobsException;
use Released\JobsBundle\Util\Options;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseJob
{

    protected $data;

    /** @var Options */
    protected $options;

    protected $processes = array();
    protected $processesOptions;

    /** @var Job */
    protected $entity = null;

    protected $needsPlanning = true;

    protected $nextPlanningAt = null;

    final public function __construct($data, Options $options = null)
    {
        $this->data = $data;
        $this->options = $options ?: new Options();
    }

    /**
     * Throw exception, if data is not valid
     */
    public function validate()
    {
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getData($name = null, $default = null)
    {
        if (is_null($name)) {
            return $this->data;
        }

        if (!array_key_exists($name, $this->data)) {
            return $default;
        }

        return $this->data[$name];
    }

    final public function runPlanning(ContainerInterface $container)
    {
        // Now we stop planning by default.
        $this->stopPlanning();

        $this->doPlan($container);
    }

    public function getNextPlanningAt()
    {
        return $this->nextPlanningAt;
    }

    /**
     * @param \DateTime $nextPlanningAt
     * @return self
     */
    public function setNextPlanningAt($nextPlanningAt)
    {
        $this->nextPlanningAt = $nextPlanningAt;

        return $this;
    }

    /**
     * @return Options
     */
    public function getProcessesOptions()
    {
        if (is_null($this->processesOptions)) {
            $this->processesOptions = new Options();
        }

        return $this->processesOptions;
    }

    /**
     * @param array|Options $processesOptions
     * @return BaseJob
     * @throws ReleasedJobsException
     */
    public function setProcessesOptions($processesOptions)
    {
        if (is_null($processesOptions) || is_array($processesOptions)) {
            $processesOptions = new Options($processesOptions);
        }

        if (!$processesOptions instanceof Options) {
            throw new ReleasedJobsException("Options must be array or instance of Options");
        }

        $this->processesOptions = $processesOptions;
        return $this;
    }

    protected function addProcess($data)
    {
        $this->processes[] = $data;
    }

    public function getProcesses()
    {
        return $this->processes;
    }

    /**
     * @return Job
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param Job $entity
     * @return self
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        $this->needsPlanning = $entity->getIsNeedPlanning();

        return $this;
    }

    public function isNeedsPlanning()
    {
        return $this->needsPlanning;
    }

    public function stopPlanning()
    {
        $this->needsPlanning = false;
    }

    /**
     * Call this method to not stop planning after first one
     */
    public function doNotStopPlanning()
    {
        $this->needsPlanning = true;
    }

    /**
     * Method is called when before it persist
     * @param ContainerInterface $container
     */
    public function onAdd(ContainerInterface $container)
    {

    }

    /**
     * @return string JobType slug
     */
    abstract public function getType();

    /**
     * Creating processes for incoming data.
     * By default there is one planning. Call doNotStopPlanning method to keep job planning.
     *
     * @param ContainerInterface $container
     * @return boolean True if need more planing, false if no more planning needed
     */
    abstract protected function doPlan(ContainerInterface $container);

    /**
     * Do whatever job needs after all packages processed
     * If job must move on, needsPlanning flag must be set to true
     * @param ContainerInterface $container For any needs, like call callback or send email
     */
    public function onFinish(ContainerInterface $container) { }

}