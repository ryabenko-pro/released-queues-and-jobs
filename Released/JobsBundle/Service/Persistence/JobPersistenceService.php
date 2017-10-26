<?php

namespace Released\JobsBundle\Service\Persistence;


use Released\Common\Doctrine\DoctrineUtils;
use Released\JobsBundle\Entity\Job;
use Released\JobsBundle\Entity\JobEvent;
use Released\JobsBundle\Entity\JobType;
use Released\JobsBundle\Model\BaseJob;
use Released\JobsBundle\Repository\JobEventRepository;
use Released\JobsBundle\Repository\JobRepository;
use Released\JobsBundle\Repository\JobTypeRepository;

class JobPersistenceService
{

    const PLANNING_LIMIT = 10;
    const PARENT_JOB_CLASS = 'Released\JobsBundle\Model\BaseJob';

    protected $config;

    /** @var DoctrineUtils */
    protected $doctrineUtils;
    /** @var JobTypeRepository */
    protected $jobTypeRepository;
    /** @var JobRepository */
    protected $jobRepository;
    /** @var JobEventRepository */
    protected $jobEventRepository;

    protected $types;

    /**
     * @param DoctrineUtils $doctrineUtils
     * @param array $config Config content job definitions, grouped by types:
     * array('types' => array(
     *  'test' => array(
     *    'job_class' => 'Full\Class\Name',
     *    'process_class' => 'Full\Class\Name',
     *    'packages_chunk' => 10,
     *  )
     * ))
     */
    function __construct(DoctrineUtils $doctrineUtils, $config)
    {
        $this->doctrineUtils = $doctrineUtils;
        $this->config = $config;

        $em = $doctrineUtils->getEntityManager();
        $this->jobTypeRepository = $em->getRepository('ReleasedJobsBundle:JobType');
        $this->jobRepository = $em->getRepository('ReleasedJobsBundle:Job');
        $this->jobEventRepository = $em->getRepository('ReleasedJobsBundle:JobEvent');
    }

    /**
     * @param BaseJob $job
     * @return int
     * @throws \Exception
     */
    public function addJob(BaseJob $job)
    {
        $job->validate();

        $type = $this->getType($job->getType());

        $entity = new Job();
        $entity
            ->setJobType($type)
            ->setData($job->getData());

        $this->jobRepository->saveJob($entity);
        $job->setEntity($entity);

        return $entity->getId();
    }

    /**
     * @return BaseJob[]
     * @throws \Exception
     */
    public function getJobsForPlanning()
    {
        // TODO [far]: select and mark planned one job to run it multiprocessing
        // TODO [far]: Use proper transaction isolation level
        $entities = $this->jobRepository->findJobsForPlanning(self::PLANNING_LIMIT);

        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->mapEntity($entity);
        }

        return $result;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getJobsForFinishing()
    {
        $entities = $this->jobRepository->findJobsForFinishing(self::PLANNING_LIMIT);

        $result = [];
        foreach ($entities as $entity) {
            $result[] = $this->mapEntity($entity);
        }

        return $result;
    }

    /**
     * @param BaseJob $job
     */
    public function markJobPlanned(BaseJob $job)
    {
        $entity = $job->getEntity();
        $entity
            ->setPlannedAt(new \DateTime())
            ->setStatus(Job::STATUS_RUN);

        $this->saveJob($job);

        $event = new JobEvent();
        $event->setJob($entity)
            ->setType($event::TYPE_PLAN);

        $this->addEvent($event);
    }

    /**
     * @param BaseJob $job
     */
    public function markJobDone(BaseJob $job)
    {
        $entity = $job->getEntity();
        $entity
            ->setFinishedAt(new \DateTime())
            ->setStatus(Job::STATUS_DONE);

        $this->saveJob($job);

        $event = new JobEvent();
        $event->setJob($entity)
            ->setType($event::TYPE_DONE);

        $this->addEvent($event);
    }

    /**
     * @param $slug
     * @throws \Exception
     * @return JobType
     */
    protected function getType($slug)
    {
        if (is_null($this->types)) {
            $this->types = [];

            /* @var $type JobType */
            foreach ($this->jobTypeRepository->findAll() as $type) {
                $this->types[$type->getSlug()] = $type;
            }
        }

        if (!isset($this->types[$slug])) {
            $type = new JobType();
            $config = $this->config['types'][$slug];
            $type->setSlug($slug)
                ->setName($config['name'])
                ->setPlanningInterval($config['planning_interval'])
                ->setPriority($config['priority']);

            $this->jobTypeRepository->saveJobType($type);

            $this->types[$slug] = $type;
        }

        return $this->types[$slug];
    }

    /**
     * @param Job $entity
     * @return BaseJob
     * @throws \Exception
     */
    public function mapEntity($entity)
    {
        $typeSlug = $entity->getJobType()->getSlug();
        $data = $entity->getData();

        $job = $this->createJobInstance($typeSlug, $data);
        $job->setEntity($entity);

        return $job;
    }

    /**
     * @param JobEvent $event
     */
    public function addEvent(JobEvent $event)
    {
        $this->jobEventRepository->saveJobEvent($event);
    }

    /**
     * @param BaseJob $job
     */
    public function saveJob(BaseJob $job)
    {
        $nextPlanningAt = $job->getNextPlanningAt();
        $interval = $this->config['types'][$job->getType()]['planning_interval'];

        // TODO: find way to select considering interval from type
        if (is_null($nextPlanningAt) && !is_null($interval)) {
            $nextPlanningAt = new \DateTime("+{$interval} seconds");
        }

        $entity = $job->getEntity();
        $entity->setData($job->getData())
            ->setNextPlanningAt($nextPlanningAt)
            ->setIsNeedPlanning($job->isNeedsPlanning());

        $this->jobRepository->saveJob($entity);
    }

    /**
     * @param string $type
     * @param mixed $data
     * @return BaseJob
     * @throws \Exception
     */
    public function createJobInstance($type, $data)
    {
        if (!isset($this->config['types'][$type])) {
            throw new \Exception("Config for job type '{$type}' not found in parameters.");
        }

        $config = $this->config['types'][$type];
        $class = $config['job_class'];
        if (!is_subclass_of($class, self::PARENT_JOB_CLASS)) {
            throw new \Exception("Job class '{$class}' must be subclass of " . self::PARENT_JOB_CLASS);
        }
        $job = new $class($data);
        return $job;
    }

}