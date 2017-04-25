<?php

namespace Released\JobsBundle\Service\Persistence;


use Released\JobsBundle\Entity\JobEvent;
use Released\JobsBundle\Entity\JobPackage;
use Released\JobsBundle\Model\BaseProcess;
use Released\JobsBundle\Repository\JobEventRepository;
use Released\JobsBundle\Repository\JobPackageRepository;
use Doctrine\ORM\EntityManager;
use Released\JobsBundle\Util\Options;

class JobProcessPersistenceService
{

    const PARENT_JOB_PROCESS_CLASS = 'Released\JobsBundle\Model\BaseProcess';
    const PACKAGES_LIMIT = 1;

    protected $config;

    /** @var EntityManager */
    protected $em;
    /** @var JobPackageRepository */
    protected $jobPackageRepository;
    /** @var JobEventRepository */
    protected $jobEventRepository;

    protected $types;

    /**
     * @param EntityManager $em
     * @param array $config Config content job definitions, grouped by types:
     * array('types' => array(
     *  'test' => array(
     *    'job_class' => 'Full\Class\Name',
     *    'process_class' => 'Full\Class\Name',
     *    'packages_chunk' => 10,
     *  )
     * ))
     */
    function __construct(EntityManager $em, $config)
    {
        $this->em = $em;
        $this->config = $config;

        $this->jobPackageRepository = $em->getRepository('ReleasedJobsBundle:JobPackage');
        $this->jobEventRepository = $em->getRepository('ReleasedJobsBundle:JobEvent');
        $this->jobRepository = $em->getRepository('ReleasedJobsBundle:Job');
    }

    public function markPackageStarted(BaseProcess $process)
    {
        $entity = $process->getEntity();
        $entity->setStatus(JobPackage::STATUS_RUN)
            ->setStartedAt(new \DateTime());

        $this->jobPackageRepository->savePackage($entity);

        $event = new JobEvent();
        $event->setJob($entity->getJob())
            ->setJobPackage($entity)
            ->setType(JobEvent::TYPE_RUN);

        $this->jobEventRepository->saveJobEvent($event);
    }

    public function markPackageFinished(BaseProcess $package)
    {
        $entity = $package->getEntity();
        $entity->setStatus(JobPackage::STATUS_DONE)
            ->setFinishedAt(new \DateTime());

        $this->jobPackageRepository->savePackage($entity);

        $event = new JobEvent();
        $event->setJob($entity->getJob())
            ->setJobPackage($entity)
            ->setType(JobEvent::TYPE_DONE);

        $this->jobEventRepository->saveJobEvent($event);

        $this->jobRepository->incPackagesFinished($entity->getJob());
    }

    /**
     * @param JobPackage $package
     */
    public function savePackage(JobPackage $package)
    {
        $this->jobPackageRepository->savePackage($package);
    }

    /**
     * @param int $id
     * @return BaseProcess
     * @throws \Exception
     */
    public function getProcess($id)
    {
        $package = $this->jobPackageRepository->getPackage($id);

        $process = $this->getProcessObject($package);

        return $process;
    }

    /**
     * @return BaseProcess[]
     * @throws \Exception
     */
    public function getProcessesForRun()
    {
        $packages = $this->jobPackageRepository->getPackagesForRun(self::PACKAGES_LIMIT);

        $result = [];
        /** @var JobPackage $package */
        foreach ($packages as $package) {
            $process = $this->getProcessObject($package);

            $result[] = $process;
        }

        return $result;
    }

    /**
     * @param JobEvent $event
     */
    public function addEvent(JobEvent $event)
    {
        $this->jobEventRepository->saveJobEvent($event);
    }

    public function updatePackageNumber(BaseProcess $process, $number)
    {
        $this->jobPackageRepository->updatePackageNumber($process->getEntity(), $number);
    }

    /**
     * @param JobPackage $package
     * @return BaseProcess
     * @throws \Exception
     */
    protected function getProcessObject($package)
    {
        $typeSlug = $package->getJob()->getJobType()->getSlug();
        if (!isset($this->config['types'][$typeSlug])) {
            throw new \Exception("Config for job type '{$typeSlug}' not found in parameters.");
        }

        $config = $this->config['types'][$typeSlug];
        $class = $config['process_class'];
        if (!is_subclass_of($class, self::PARENT_JOB_PROCESS_CLASS)) {
            throw new \Exception("Job process class '{$class}' must be subclass of " . self::PARENT_JOB_PROCESS_CLASS);
        }

        /** @var BaseProcess $process */
        $process = new $class($package->getPackages(), new Options($package->getOptions()));
        $process->setEntity($package);
        return $process;
    }

}