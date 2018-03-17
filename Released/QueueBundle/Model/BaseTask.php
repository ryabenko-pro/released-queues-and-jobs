<?php


namespace Released\QueueBundle\Model;


use Released\QueueBundle\Entity\QueuedTask;
use Released\QueueBundle\Exception\TaskAddException;
use Released\QueueBundle\Exception\TaskExecutionException;
use Released\QueueBundle\Exception\TaskRetryException;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseTask
{

    protected $data;
    protected $retries = 0;

    /** @var BaseTask[] */
    protected $next = [];

    /** @var QueuedTask */
    protected $entity;

    /** @var \DateTime */
    protected $scheduledAt;

    /**
     * @param array $data
     * @param QueuedTask $entity
     * @param \DateTime $scheduledAt
     */
    final public function __construct($data = [], QueuedTask $entity = null, \DateTime $scheduledAt = null)
    {
        $this->validateData($data);

        $this->data = $data;
        $this->entity = $entity;
        $this->scheduledAt = $scheduledAt;
    }

    /**
     * Check data provided. Should trow exception, if data not valid.
     *
     * @param array $data
     * @throws TaskAddException
     */
    public function validateData($data) { }

    /**
     * Before task added into queue.
     * !WARNING! this method and `execute` very likely to be executed in different context.
     * Do not make execute method depended on preExecute
     *
     * @param ContainerInterface $container
     * @param TaskLoggerInterface $logger
     * @return mixed|void
     */
    public function beforeAdd(ContainerInterface $container, TaskLoggerInterface $logger) { }

    /**
     * Run task
     *
     * @param ContainerInterface $container
     * @param TaskLoggerInterface $logger
     * @return mixed
     *
     * @throws TaskRetryException
     */
    abstract public function execute(ContainerInterface $container, TaskLoggerInterface $logger);

    /**
     * Returns the type of task to be executed
     * @return string
     */
    abstract public function getType();

    /**
     * @param string|null $param Param name to return
     * @param mixed $default Default for param
     * @return mixed
     */
    public function getData($param = null, $default = null)
    {
        if (is_null($param)) {
            return $this->data;
        }

        if (array_key_exists($param, $this->data)) {
            return $this->data[$param];
        }

        return $default;
    }

    public function fail($reason = null)
    {
        if (is_null($this->entity)) {
            throw new TaskExecutionException("Task execution failed for reason: '{$reason}'");
        }

        $this->entity
            ->addLog("Failed: '{$reason}'")
            ->setState(QueuedTask::STATE_FAIL);
    }

    public function hasNextTasks(): bool
    {
        return count($this->next) > 0;
    }

    /**
     * @return BaseTask[]
     */
    public function getNextTasks()
    {
        return $this->next;
    }

    /**
     * @param BaseTask $next
     * @return self
     */
    public function addNextTask(BaseTask $next)
    {
        $this->next[] = $next;

        return $this;
    }

    /**
     * @param null $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return QueuedTask
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param QueuedTask $entity
     * @return self
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    public function setRetries(int $retries): self
    {
        $this->retries = $retries;

        return $this;
    }

    public function incRetries(): int
    {
        $this->retries++;

        return $this->retries;
    }

    /**
     * @return \DateTime
     */
    public function getScheduledAt()
    {
        return $this->scheduledAt;
    }
    /**
     * @param \DateTime $scheduledAt
     */
    public function setScheduledAt($scheduledAt)
    {
        $this->scheduledAt = $scheduledAt;
    }

}