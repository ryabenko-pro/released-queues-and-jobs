<?php

namespace Released\JobsBundle\Service;


use Released\JobsBundle\Entity\JobEvent;
use Released\JobsBundle\Interfaces\ProcessExecutorInterface;
use Released\JobsBundle\Model\BaseProcess;
use Released\JobsBundle\Service\Persistence\JobProcessPersistenceService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProcessExecutorService implements ProcessExecutorInterface
{
    /** @var JobProcessPersistenceService */
    protected $processPersistence;

    /** @var ContainerInterface */
    protected $container;

    function __construct(JobProcessPersistenceService $processPersistence, ContainerInterface $container)
    {
        $this->processPersistence = $processPersistence;
        $this->container = $container;
    }

    public function executeProcesses()
    {
        $processes = $this->processPersistence->getProcessesForRun();

        foreach ($processes as $process) {
            $this->runProcess($process);
        }
    }

    /**
     * @param $id
     * @return BaseProcess
     */
    public function getProcess($id)
    {
        return $this->processPersistence->getProcess($id);
    }

    /**
     * @inheritdoc
     */
    public function runProcess(BaseProcess $process)
    {
        $this->processPersistence->markPackageStarted($process);
        $process->execute($this, $this->container);
        $this->processPersistence->markPackageFinished($process);
    }

    /**
     * @inheritdoc
     */
    public function addError(BaseProcess $process, $currentPackage, $error)
    {
        $event = new JobEvent();
        $entity = $process->getEntity();
        $event->setJob($entity->getJob())
            ->setJobPackage($entity)
            ->setJobPackageNumber($currentPackage)
            ->setType(JobEvent::TYPE_ERROR)
            ->setMessage($error);

        $this->processPersistence->addEvent($event);
    }

    /**
     * @inheritdoc
     */
    public function updatePackageNumber(BaseProcess $process, $number)
    {
        $this->processPersistence->updatePackageNumber($process, $number);
    }

    /**
     * @inheritdoc
     */
    public function addLog(BaseProcess $process, $message, $currentPackage = 0)
    {
        $event = new JobEvent();
        $entity = $process->getEntity();
        $event->setJob($entity->getJob())
            ->setJobPackage($entity)
            ->setJobPackageNumber($currentPackage)
            ->setType(JobEvent::TYPE_LOG)
            ->setMessage($message);

        $this->processPersistence->addEvent($event);
    }

}