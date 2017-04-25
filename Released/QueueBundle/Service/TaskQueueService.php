<?php


namespace Released\QueueBundle\Service;


use PHPUnit\Framework\ExpectationFailedException;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Entity\QueuedTask;
use Released\QueueBundle\Exception\TaskAddException;
use Released\QueueBundle\Exception\TaskRetryException;
use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Repository\QueuedTaskRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * This class add task into queue to be executed later.
 * Good for prod env
 */
class TaskQueueService implements TaskExecutorInterface, TaskLoggerInterface
{
    const PARENT_TASK_CLASS = 'Released\QueueBundle\Model\BaseTask';

    /** @var ContainerInterface */
    protected $container;
    /** @var QueuedTaskRepository */
    protected $queuedTaskRepository;
    /** @var ConfigQueuedTaskType[] */
    protected $types;

    /**
     * @param ContainerInterface $container
     * @param QueuedTaskRepository $queuedTaskRepository
     * @param ConfigQueuedTaskType[] $types
     */
    function __construct($container, $queuedTaskRepository, $types)
    {
        $this->container = $container;
        $this->queuedTaskRepository = $queuedTaskRepository;
        $this->types = $types;
    }

    /**
     * Enqueue task to execute is later in background
     * @inheritdoc
     */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {
        if (!is_null($parent) && is_null($parent->getEntity())) {
            $this->addTask($parent);
        }

        $task->beforeAdd($this->container, $this);

        $typeName = $task->getType();
        $type = $this->getType($typeName);

        $entity = new QueuedTask();
        $entity->setPriority($type->getPriority())
            ->setType($typeName)
            ->setData($task->getData());
        
        $entity->setScheduledAt($task->getScheduledAt());

        $task->setEntity($entity);

        if (!is_null($parent)) {
            $entity->setParent($parent->getEntity()->getId());
        }

        return $this->queuedTaskRepository->saveQueuedTask($entity);
    }

    /**
     * Execute task. Usually from background cron command.
     * @param BaseTask $task
     * @return null|void
     */
    public function executeTask(BaseTask $task)
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

        try {
            ob_start();
            $task->execute($this->container, $this);
            $log = $this->catchOutput();

            $entity->setState($entity::STATE_DONE)
                ->addLog($log);
        } catch (TaskRetryException $exception) {
            if ($entity::STATE_RETRY == $entity->getState()) {
                // Don't retry again.
                // TODO: add retry count and default value in configuration
                $entity->setState($entity::STATE_FAIL)
                    ->addLog($this->catchOutput(), 'info');

                $log = sprintf("[%s]: %s", get_class($exception), $exception->getMessage());
                $entity->addLog($log, 'retry');
                $entity->addLog('Retry limit exceeded', 'fail');
            } else {
                $entity->setState($entity::STATE_RETRY)
                    ->addLog($this->catchOutput(), 'info');
                $log = sprintf("[%s]: %s", get_class($exception), $exception->getMessage());
                $time = sprintf("+%d seconds", $exception->getTimeout() ?: 60);

                $entity
                    ->setScheduledAt(new \DateTime($time))
                    ->addLog($log, 'retry');
            }

        } catch (ExpectationFailedException $exception) {
            $this->catchOutput();
            throw $exception;
//        } catch (UnexpectedHTTPCodeException $ex) {
//            $this->container->get('event_dispatcher')
//                ->dispatch(QueueEventNames::UNEXPECTED_HTTP_CODE, new QueueExceptionEvent($ex->getMessage()));
        } catch (\Exception $exception) {
            $entity->setState($entity::STATE_FAIL)
                ->addLog($this->catchOutput(), 'info');
            $log = sprintf("[%s]: %s", get_class($exception), $exception->getMessage());
            $entity->addLog($log, 'error');
        }
        $entity->setFinishedAt(new \DateTime());

        $this->queuedTaskRepository->saveQueuedTask($entity);

        if ($entity->isDone()) {
            $this->queuedTaskRepository->updateDependTasks($entity);
        }
    }

    /**
     * @param QueuedTask $entity
     * @throws \Exception
     * @return BaseTask
     */
    public function mapEntityToTask($entity)
    {
        $type = $this->getType($entity->getType());

        $class = $type->getClassName();
        if (!is_subclass_of($class, self::PARENT_TASK_CLASS)) {
            throw new TaskAddException("Task class '{$class}' must be subclass of " . self::PARENT_TASK_CLASS);
        }

        $task = new $class($entity->getData(), $entity);

        return $task;
    }

    public function runTasks($types = null)
    {
        /** @var BaseTask[] $tasks */
        $tasks = [];

        $entities = $this->queuedTaskRepository->getQueuedTasksForRun($types);
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

        return $content;
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
            $type = new ConfigQueuedTaskType($type['name'], $type['class_name'], $type['priority']);
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
}