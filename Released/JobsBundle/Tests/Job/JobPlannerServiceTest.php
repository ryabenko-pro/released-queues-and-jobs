<?php

namespace Released\JobsBundle\Tests\Job;


use Released\JobsBundle\Entity\Job;
use Released\JobsBundle\Entity\JobEvent;
use Released\JobsBundle\Entity\JobPackage;
use Released\JobsBundle\Entity\JobType;
use Released\JobsBundle\Model\BaseJob;
use Released\JobsBundle\Service\JobPlannerService;
use Released\JobsBundle\Tests\BaseJobsTestCase;
use Released\JobsBundle\Tests\Stub\StubJob;
use Released\JobsBundle\Util\Options;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class JobPlannerServiceTest extends BaseJobsTestCase
{

    public function testShouldPlanJob()
    {
        // GIVEN
        $jobPersist = $this->getJobPersistenceServiceMock(['getJobsForPlanning', 'markJobPlanned']);
        $jobProcessPersist = $this->getJobPersistenceServiceMock(['savePackage']);

        $entity = $this->createEntity();
        $entity->setPackagesTotal(2);

        $job = new StubJob([]);
        $processesOptions = ['options'];
        $job->setType('test')
            ->setProcesses([1, 2, 3, 4, 5, 6, 7, 8, 9, 0])
            ->setProcessesOptions($processesOptions)
            ->setEntity($entity);

        $package = new JobPackage();
        $package->setJob($entity)
            ->setOptions($processesOptions)
            ->setPackages([1, 2, 3, 4, 5, 6]);
        $jobProcessPersist->expects($this->at(0))->method('savePackage')
            ->with($this->equalTo($package));

        $package = new JobPackage();
        $package->setJob($entity)
            ->setOptions($processesOptions)
            ->setPackages([7, 8, 9, 0]);
        $jobProcessPersist->expects($this->at(1))->method('savePackage')
            ->with($this->equalTo($package));

        $expectedEntity = $this->createEntity();
        $expectedEntity->setPackagesTotal(4);

        $expectedJob = clone $job;
        $expectedJob->setEntity($expectedEntity);
        $expectedJob->stopPlanning();
        $jobPersist->expects($this->once())->method('markJobPlanned')
            ->with($expectedJob);

        $config = ['types' => []];
        $config['types']['test'] = [
            'job_class' => '\Released\JobsBundle\Tests\Stub\StubJob',
            'process_class' => '\Released\JobsBundle\Tests\Stub\StubProcess',
            'packages_chunk'   => 6,
        ];
        $planner = new JobPlannerService(new Container(), $jobPersist, $jobProcessPersist, $config);

        // WHEN
        $planner->doJobPlanning($job);
    }

    public function testShouldFinishJob()
    {
        // GIVEN
        $jobPersist = $this->getJobPersistenceServiceMock(['markJobDone']);
        $jobProcessPersist = $this->getJobPersistenceServiceMock(['savePackage']);

        $job = $this->getMockBuilder('Released\JobsBundle\Tests\Stub\StubJob')
            ->setMethods(['onFinish', 'isNeedsPlanning'])
            ->disableOriginalConstructor()->getMock();
        /** @var $job StubJob|\PHPUnit_Framework_MockObject_MockObject */

        $entity = $this->createEntity();
        $job->setType('test')
            ->setEntity($entity);

        $container = new Container();

        $job->expects($this->once())->method('onFinish')
            ->with($this->equalTo($container));
        $job->expects($this->once())->method('isNeedsPlanning')
            ->willReturn(false);

        $jobPersist->expects($this->once())->method('markJobDone')
            ->with($this->equalTo($job));

        $planner = new JobPlannerService($container, $jobPersist, $jobProcessPersist, []);

        // WHEN
        $planner->finishJob($job, $container);
    }

    public function testShouldContinueNeedPlanningJobAfterFinish()
    {
        // GIVEN
        $jobPersist = $this->getJobPersistenceServiceMock(['saveJob', 'addEvent']);
        $jobProcessPersist = $this->getJobPersistenceServiceMock(['savePackage']);

        $job = $this->getMockBuilder('Released\JobsBundle\Tests\Stub\StubJob')
            ->setMethods(['onFinish', 'isNeedsPlanning'])
            ->disableOriginalConstructor()->getMock();
        /** @var $job StubJob|\PHPUnit_Framework_MockObject_MockObject */

        $entity = $this->createEntity();
        $job->setType('test')
            ->setEntity($entity);

        $container = new Container();

        $job->expects($this->once())->method('onFinish')
            ->with($this->equalTo($container));
        $job->expects($this->once())->method('isNeedsPlanning')
            ->willReturn(true);

        $event = new JobEvent();
        $event->setType($event::TYPE_REPLAN)
            ->setJob($entity);

        $jobPersist->expects($this->once())->method('saveJob')
            ->with($this->equalTo($job));
        $jobPersist->expects($this->once())->method('addEvent')
            ->with($this->equalTo($event));

        $planner = new JobPlannerService($container, $jobPersist, $jobProcessPersist, []);

        // WHEN
        $planner->finishJob($job, $container);
    }

    public function testShouldCatchErrorOnFinalize()
    {
        // GIVEN
        $jobPersist = $this->getJobPersistenceServiceMock(['addEvent']);
        $jobProcessPersist = $this->getJobPersistenceServiceMock(['savePackage']);

        $job = $this->getMockBuilder('Released\JobsBundle\Tests\Stub\StubJob')
            ->setMethods(['onFinish'])
            ->disableOriginalConstructor()->getMock();
        /** @var $job StubJob|\PHPUnit_Framework_MockObject_MockObject */

        $entity = $this->createEntity();
        $job->setType('test')
            ->setEntity($entity);

        $event = new JobEvent();
        $event->setJob($entity)
            ->setType($event::TYPE_ERROR)
            ->setMessage("Error while calling onFinish callback: 'Something happened exception.'");

        $container = new Container();

        $job->expects($this->once())->method('onFinish')
            ->willThrowException(new \Exception("Something happened exception."));

        $jobPersist->expects($this->once())->method('addEvent')
            ->with($this->equalTo($event));

        $planner = new JobPlannerService($container, $jobPersist, $jobProcessPersist, []);

        // WHEN
        $planner->finishJob($job, $container);
    }

    public function testShouldLogOutputOnFinalize()
    {
        // GIVEN
        $jobPersist = $this->getJobPersistenceServiceMock(['markJobDone']);
        $jobProcessPersist = $this->getJobPersistenceServiceMock(['addEvent']);

        $job = EchoJob::create('Hello from planning', 'Hello from finishing');
        /** @var $job StubJob|\PHPUnit_Framework_MockObject_MockObject */

        $entity = $this->createEntity();
        $job->setEntity($entity);

        $event = new JobEvent();
        $event->setJob($entity)
            ->setType($event::TYPE_LOG)
            ->setMessage("On finish: Hello from finishing");

        $container = new Container();

        $jobProcessPersist->expects($this->once())->method('addEvent')
            ->with($event);

        $planner = new JobPlannerService($container, $jobPersist, $jobProcessPersist, []);

        // WHEN
        $planner->finishJob($job, $container);
    }

    public function testShouldLogOutputOnPlanning()
    {
        // GIVEN
        $jobPersist = $this->getJobPersistenceServiceMock(['markJobPlanned']);
        $jobProcessPersist = $this->getJobPersistenceServiceMock(['addEvent']);

        $job = EchoJob::create('Hello from planning', 'Hello from finishing');
        /** @var $job StubJob|\PHPUnit_Framework_MockObject_MockObject */

        $entity = $this->createEntity();
        $job->setEntity($entity);

        $event = new JobEvent();
        $event->setJob($entity)
            ->setType($event::TYPE_LOG)
            ->setMessage("On planning: Hello from planning");

        $container = new Container();

        $jobProcessPersist->expects($this->once())->method('addEvent')
            ->with($event);

        $planner = new JobPlannerService($container, $jobPersist, $jobProcessPersist, [
            'types' => [
                'echo'  => [
                    'name' => 'Echo',
                    'job_class' => 'EchoJob',
                    'process_class' => 'EchoProcess',
                    'packages_chunk'    => 10,
                ]
            ],
        ]);

        // WHEN
        $planner->doJobPlanning($job);
    }

    private function createEntity()
    {
        $type = new JobType();
        $type->setSlug('test')->setName('test');

        $entity = new Job();
        $entity->setData([])
            ->setJobType($type);

        return $entity;
    }

}

class EchoJob extends BaseJob
{

    static public function create($planning, $finishing)
    {
        return new EchoJob([
            'planning' => $planning,
            'finishing' => $finishing,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return "echo";
    }

    /**
     * Creating processes for incoming data
     * @param ContainerInterface $container
     * @return boolean True if need more planning, false if no more planning needed
     */
    protected function doPlan(ContainerInterface $container)
    {
        if (!is_null($this->getData('planning'))) {
            echo $this->getData('planning');
        }
    }

    public function onFinish(ContainerInterface $container)
    {
        if (!is_null($this->getData('finishing'))) {
            echo $this->getData('finishing');
        }
    }

    public function isNeedsPlanning()
    {
        return false;
    }

}