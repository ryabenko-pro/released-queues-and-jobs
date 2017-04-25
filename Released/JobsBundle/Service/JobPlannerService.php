<?php

namespace Released\JobsBundle\Service;


use Released\JobsBundle\Entity\JobEvent;
use Released\JobsBundle\Entity\JobPackage;
use Released\JobsBundle\Model\BaseJob;
use Released\JobsBundle\Service\Persistence\JobPersistenceService;
use Released\JobsBundle\Service\Persistence\JobProcessPersistenceService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobPlannerService
{

    /** @var ContainerInterface */
    protected $container;

    /** @var JobPersistenceService */
    protected $jobPersistence;

    /** @var JobProcessPersistenceService */
    protected $processPersistence;
    protected $config;

    function __construct(ContainerInterface $container, $jobPersistence, $processPersistence, $config)
    {
        $this->container = $container;
        $this->jobPersistence = $jobPersistence;
        $this->processPersistence = $processPersistence;
        $this->config = $config;
    }

    /**
     * @param BaseJob $job
     * @return int
     */
    public function addJob(BaseJob $job)
    {
        $job->validate();

        $job->onAdd($this->container);

        return $this->jobPersistence->addJob($job);
    }

    public function runPlanning()
    {
        foreach ($this->jobPersistence->getJobsForPlanning() as $job) {
            $this->doJobPlanning($job);
        }
    }

    public function runFinishing()
    {
        foreach ($this->jobPersistence->getJobsForFinishing() as $job) {
            $this->finishJob($job);
        }
    }

    public function doJobPlanning(BaseJob $job)
    {
        $type = $job->getType();
        if (!isset($this->config['types'][$type])) {
            throw new \Exception("Job type '{$type}' is not defined in config.");
        }

        $chunkSize = $this->config['types'][$type]['packages_chunk'];

        ob_start();
        $job->runPlanning($this->container);
        $this->addLog($job, 'planning');

        $processes = $job->getProcesses();

        $packages = array_chunk($processes, $chunkSize);

        foreach ($packages as $data) {
            $package = new JobPackage();
            $package->setJob($job->getEntity())
                ->setOptions($job->getProcessesOptions()->getAll())
                ->setPackages($data);

            $this->processPersistence->savePackage($package);
        }

        $job->getEntity()->incPackagesTotal(count($packages));
        $this->jobPersistence->markJobPlanned($job);
    }

    public function finishJob(BaseJob $job)
    {
        try {
            ob_start();
            $job->onFinish($this->container);
            $this->addLog($job, 'finish');

            if (!$job->isNeedsPlanning()) {
                $this->jobPersistence->markJobDone($job);
            } else {
                $this->jobPersistence->saveJob($job);

                $event = new JobEvent();
                $event->setJob($job->getEntity())
                    ->setType($event::TYPE_REPLAN);
                $this->jobPersistence->addEvent($event);
            }
        } catch (\Exception $exception) {
            $this->addLog($job, 'finish');

            $event = new JobEvent();
            $event->setJob($job->getEntity())
                ->setType($event::TYPE_ERROR)
                ->setMessage("Error while calling onFinish callback: '" . $exception->getMessage() . "'");

            $this->jobPersistence->addEvent($event);
        }
    }

    /**
     * @param string $type
     * @param mixed $data
     * @return BaseJob
     * @throws \Exception
     */
    public function createJobInstance($type, $data)
    {
        return $this->jobPersistence->createJobInstance($type, $data);
    }

    /**
     * @param BaseJob $job
     * @param string $eventType
     */
    protected function addLog(BaseJob $job, $eventType)
    {
        $output = ob_get_contents();
        $output = trim($output);
        ob_end_clean();

        if (!empty($output)) {
            $event = new JobEvent();
            $event->setJob($job->getEntity())
                ->setType(JobEvent::TYPE_LOG)
                ->setMessage("On {$eventType}: " . $output);

            $this->processPersistence->addEvent($event);
        }
    }

}