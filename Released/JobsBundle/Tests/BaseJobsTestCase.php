<?php

namespace Released\JobsBundle\Tests;


use Released\JobsBundle\Repository\JobEventRepository;
use Released\JobsBundle\Repository\JobPackageRepository;
use Released\JobsBundle\Repository\JobRepository;
use Released\JobsBundle\Repository\JobTypeRepository;
use Released\JobsBundle\Service\Persistence\JobPersistenceService;
use Released\JobsBundle\Service\Persistence\JobProcessPersistenceService;
use Released\JobsBundle\Service\ProcessExecutorService;
use Released\JobsBundle\Tests\Stub\StubProcess;
use PHPUnit_Framework_MockObject_MockObject;

class BaseJobsTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @param array $calls ['...:...Repository' => $repository]
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntityManagerMock($calls = [])
    {
        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])->getMock();

        $i = 0;
        foreach ($calls as $repo => $result) {
            if (is_numeric($repo)) {
                $i = $repo;
                $em->expects($this->at($i++))->method('getRepository')
                    ->with($result[0])->willReturn($result[1]);
            } else {
                $em->expects($this->at($i++))->method('getRepository')
                    ->with($repo)->willReturn($result);
            }
        }

        return $em;
    }

    /**
     * @param array $methods
     * @return JobPackageRepository|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getPackageRepositoryMock($methods = ['savePackage', 'getPackagesForRun'])
    {
        $jobPackageRepository = $this->getMockBuilder('Released\JobsBundle\Repository\JobPackageRepository')
            ->disableOriginalConstructor()
            ->setMethods((array)$methods)->getMock();
        return $jobPackageRepository;
    }

    /**
     * @param array $methods
     * @return JobRepository|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getJobRepositoryMock($methods = ['saveJob', 'findAll'])
    {
        $jobTypeRepository = $this->getMockBuilder('Released\JobsBundle\Repository\JobRepository')
            ->disableOriginalConstructor()
            ->setMethods((array)$methods)->getMock();

        return $jobTypeRepository;
    }

    /**
     * @param array $methods
     * @return JobTypeRepository|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getJobTypeRepositoryMock($methods = ['findAll'])
    {
        $jobTypeRepository = $this->getMockBuilder('Released\JobsBundle\Repository\JobTypeRepository')
            ->disableOriginalConstructor()
            ->setMethods((array)$methods)->getMock();
        return $jobTypeRepository;
    }

    /**
     * @param array $methods
     * @return JobEventRepository|PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEventRepositoryMock($methods = ['saveJobEvent'])
    {
        $jobEventRepository = $this->getMockBuilder('Released\JobsBundle\Repository\JobEventRepository')
            ->disableOriginalConstructor()
            ->setMethods((array)$methods)->getMock();

        return $jobEventRepository;
    }

    /**
     * @param $methods
     * @return JobProcessPersistenceService|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getProcessPersistenceMock($methods)
    {
        $processPersistence = $this->getMockBuilder('\Released\JobsBundle\Service\Persistence\JobProcessPersistenceService')
            ->disableOriginalConstructor()
            ->setMethods((array)$methods)->getMock();

        return $processPersistence;
    }

    /**
     * @param $methods
     * @return StubProcess|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getProcessMock($methods)
    {
        $process = $this->getMockBuilder('\Released\JobsBundle\Tests\Stub\StubProcess')
            ->disableOriginalConstructor()
            ->setMethods((array)$methods)->getMock();

        return $process;
    }

    /**
     * @param $methods
     * @return ProcessExecutorService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProcessExecutorMock($methods)
    {
        $executor = $this->getMockBuilder('\Released\JobsBundle\Service\ProcessExecutorService')
            ->disableOriginalConstructor()
            ->setMethods($methods)->getMock();

        return $executor;
    }

    /**
     * @param $methods
     * @return JobPersistenceService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getJobPersistenceServiceMock($methods = ['getJobsForPlanning', 'markJobPlanned'])
    {
        $jobPersist = $this->getMockBuilder('Released\JobsBundle\Service\Persistence\JobPersistenceService')
            ->disableOriginalConstructor()
            ->setMethods($methods)->getMock();

        return $jobPersist;
    }

    /**
     * @param $methods
     * @return JobProcessPersistenceService|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getProcessPersistenceServiceMock($methods = ['savePackage'])
    {
        $jobProcessPersist = $this->getMockBuilder('Released\JobsBundle\Service\Persistence\JobProcessPersistenceService')
            ->setMethods($methods)
            ->disableOriginalConstructor()->getMock();

        return $jobProcessPersist;
    }

}