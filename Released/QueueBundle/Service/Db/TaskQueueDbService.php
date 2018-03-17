<?php


namespace Released\QueueBundle\Service\Db;


use PHPUnit\Framework\Exception as TestException;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Entity\QueuedTask;
use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Exception\TaskAddException;
use Released\QueueBundle\Exception\TaskRetryException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Repository\QueuedTaskRepository;
use Released\QueueBundle\Service\EnqueuerInterface;
use Released\QueueBundle\Service\Logger\TaskLoggerAggregate;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * This class add task into database to be executed in separate environment
 */
class TaskQueueDbService implements EnqueuerInterface, TaskLoggerInterface
{

    /** @var ContainerInterface */
    protected $container;
    /** @var QueuedTaskRepository */
    protected $queuedTaskRepository;
    /** @var ConfigQueuedTaskType[] */
    protected $types;
    /** @var null|string */
    protected $serverId;

    /**
     * @param ContainerInterface $container
     * @param QueuedTaskRepository $queuedTaskRepository
     * @param ConfigQueuedTaskType[] $types
     * @param string|null $serverId ID of the server to run local tasks
     */
    function __construct(ContainerInterface $container, QueuedTaskRepository $queuedTaskRepository, $types, $serverId = null)
    {
        $this->container = $container;
        $this->queuedTaskRepository = $queuedTaskRepository;
        $this->types = $types;
        $this->serverId = $serverId;
    }

    /**
     * Enqueue task to execute is later in background
     * @inheritdoc
     */
    public function enqueue(BaseTask $task)
    {
        return $this->doEnqueue($task);
    }

    /**
     * @param BaseTask $task
     * @param BaseTask|null $parent
     * @return int
     */
    protected function doEnqueue(BaseTask $task, BaseTask $parent = null)
    {
        $task->beforeAdd($this->container, $this);

        $typeName = $task->getType();
        $type = $this->getType($typeName);

        $entity = new QueuedTask();
        $entity->setPriority($type->getPriority())
            ->setType($typeName)
            ->setData($task->getData());

        if ($type->isLocal()) {
            $entity->setServer($this->serverId);
        }

        $entity->setScheduledAt($task->getScheduledAt());

        $task->setEntity($entity);

        $this->queuedTaskRepository->saveQueuedTask($entity);
        if (!is_null($parent)) {
            $entity->setParent($parent->getEntity()->getId());
        }

        if ($task->hasNextTasks()) {
            foreach ($task->getNextTasks() as $nextTask) {
                $this->doEnqueue($nextTask, $task);
            }
        }

        return $entity->getId();
    }

    /** {@inheritdoc} */
    public function retry(BaseTask $task)
    {
        $entity = $task->getEntity();
        if (is_null($entity)) {
            throw new TaskAddException("Can't retry task that was not persisted before");
        }

        $entity->setTries($task->incRetries());
        $this->queuedTaskRepository->saveQueuedTask($entity);
    }

    /**
     * @param int $id
     * @param TaskLoggerInterface|null $logger
     * @throws \RuntimeException
     */
    public function runTaskById($id, TaskLoggerInterface $logger = null)
    {
        /** @var QueuedTask $entity */
        $entity = $this->queuedTaskRepository->find($id);

        $task = $this->mapEntityToTask($entity);

        $this->executeTask($task, $logger);
    }

    /**
     * Execute task. Usually from background cron command.
     * @param BaseTask $task
     * @param TaskLoggerInterface|null $logger
     * @return null|void
     */
    public function executeTask(BaseTask $task, TaskLoggerInterface $logger = null)
    {
        $entity = $task->getEntity();

        if ($entity->isCancelled()) {
            $entity->addLog(sprintf('Task is cancelled. It cannot be executed anymore'));

            return;
        }

        if ($entity->isWaiting()) {
            $entity->addLog(sprintf('Task is waiting. Skipping.'));

            return null;
        }

        $this->queuedTaskRepository->setTaskStarted($entity, getmypid());

        $logger = is_null($logger) ? $this : new TaskLoggerAggregate([$this, $logger]);

        try {
            ob_start();
            $task->execute($this->container, $logger);
            $entity->setState($entity::STATE_DONE);

            $output = $this->catchOutput();
            if (!empty($output)) {
                $entity->addLog($output, 'info');
            }
        } catch (TaskRetryException $exception) {
            $this->handleRetryException($task, $entity, $exception);

        } catch (TestException $exception) {
            $this->catchOutput();

            throw $exception;
        } catch (\Exception $exception) {
            $entity->setState($entity::STATE_FAIL);
            $output = $this->catchOutput();
            if (!empty($output)) {
                $entity->addLog($output, 'info');
            }

            $log = sprintf("[%s]: %s", get_class($exception), $exception->getMessage());
            $entity->addLog($log, 'error');
        }
        $entity->setFinishedAt(new \DateTime());

        $this->queuedTaskRepository->saveQueuedTask($entity);

        if ($entity->isDone()) {
            $this->queuedTaskRepository->updateDependTasks($entity);
        }
    }

    protected function handleRetryException(BaseTask $task, QueuedTask $entity, TaskRetryException $exception): void
    {
        $type = $this->getType($task->getType());

        if ($entity->getTries() >= $type->getRetryLimit()) {
            // Don't retry again.
            $entity->setState($entity::STATE_FAIL);

            $output = $this->catchOutput();
            if (!empty($output)) {
                $entity->addLog($output, 'info');
            }

            $log = sprintf("[%s]: %s", get_class($exception), $exception->getMessage());
            $entity->addLog($log, 'retry');
            $entity->addLog("Retry limit ({$type->getRetryLimit()}) exceeded", 'fail');
        } else {
            $log = sprintf("[%s]: %s", get_class($exception), $exception->getMessage());

            $output = $this->catchOutput();
            if (!empty($output)) {
                $entity->addLog($output, 'info');
            }

            $entity
                ->setState($entity::STATE_RETRY)
                // TODO: make it an expression
                ->setTries($entity->getTries() + 1)
                ->addLog($log, 'retry');

            $timeout = $exception->getTimeout();
            if (!is_null($timeout) && is_int($timeout)) {
                $entity->setScheduledAt(new \DateTime("+{$timeout} seconds"));
            }
        }
    }

    /**
     * @param QueuedTask $entity
     * @throws \RuntimeException
     * @return BaseTask
     */
    public function mapEntityToTask(QueuedTask $entity)
    {
        $type = $this->getType($entity->getType());

        $class = $type->getClassName();
        if (!is_subclass_of($class, BaseTask::class)) {
            throw new TaskAddException("Task class '{$class}' must be subclass of " . BaseTask::class);
        }

        $task = new $class($entity->getData(), $entity);

        return $task;
    }

    /**
     * @param string[]|null $types
     * @param string[]|null $noTypes
     * @throws \Exception
     */
    public function runTasks($types = null, $noTypes = null)
    {
        /** @var BaseTask[] $tasks */
        $tasks = [];

        $entities = $this->queuedTaskRepository->getQueuedTasksForRun($types, $noTypes, $this->serverId);
        foreach ($entities as $entity) {
            $tasks[] = $this->mapEntityToTask($entity);
        }

        foreach ($tasks as $task) {
            $this->executeTask($task);
        }
    }

    private function catchOutput()
    {
        $content = ob_get_contents();
        ob_end_flush();

        return trim($content);
    }

    /**
     * @param $typeName
     * @return ConfigQueuedTaskType
     */
    private function getType($typeName)
    {
        if (!isset($this->types[$typeName])) {
            throw new TaskAddException("Type '{$typeName}' not found");
        }

        $type = $this->types[$typeName];
        if (is_array($type)) {
            $type = new ConfigQueuedTaskType($type['name'], $type['class_name'], $type['priority'], $type['local'], $type['retry_limit']);
            $this->types[$typeName] = $type;
        }

        return $type;
    }

    /**
     * Redefine container to be able extend parameters
     * @param ContainerInterface $container
     * @return self
     */
    public function setContainer($container)
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    public function log(BaseTask $task, $message, $type = self::LOG_MESSAGE)
    {
        $task->getEntity()->addLog($message, $type);
    }

    /**
     * @return null|string
     */
    public function getServerId()
    {
        return $this->serverId;
    }

    /**
     * @param null|string $serverId
     * @return self
     */
    public function setServerId($serverId)
    {
        $this->serverId = $serverId;
        return $this;
    }

    /** {@inheritdoc} */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {
        throw new BCBreakException('You must use {enqueue} method now.');
    }
}