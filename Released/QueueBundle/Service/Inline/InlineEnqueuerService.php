<?php

namespace Released\QueueBundle\Service\Inline;

use Released\QueueBundle\Entity\QueuedTask;
use Released\QueueBundle\Exception\BCBreakException;
use Released\QueueBundle\Model\BaseTask;
use Psr\Log\LoggerInterface;
use Released\QueueBundle\Service\EnqueuerInterface;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This class just runs task in the same thread as client code.
 * Good for dev env.
 */
class InlineEnqueuerService implements EnqueuerInterface, TaskLoggerInterface
{

    /** @var ContainerInterface */
    protected $container;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ContainerInterface $container
     * @param LoggerInterface $logger
     */
    function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function addTask(BaseTask $task, BaseTask $parent = null)
    {
        throw new BCBreakException('You must use {enqueue} method now.');
    }

    /** {@inheritdoc} */
    public function enqueue(BaseTask $task)
    {
        $task->beforeAdd($this->container, $this);
        $task->execute($this->container, $this);

        return null;
    }

    /**
     * @inheritdoc
     */
    public function log(BaseTask $task, $message, $type = self::LOG_MESSAGE)
    {
        $log = sprintf("Task[%s](%s): %s", $task->getType(), json_encode($task->getData()), $message);

        switch ($type) {
            case self::LOG_ERROR:
                $this->logger->error($log);
                break;
            case self::LOG_NOTICE:
                $this->logger->notice($log);
                break;
            default:
                $this->logger->info($log);
        }
    }
}
