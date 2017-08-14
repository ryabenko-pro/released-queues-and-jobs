<?php


namespace Released\QueueBundle\Tests\Service;


use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Entity\QueuedTask;
use Released\QueueBundle\Exception\TaskExecutionException;
use Released\QueueBundle\Exception\TaskRetryException;
use Released\QueueBundle\Repository\QueuedTaskRepository;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Released\QueueBundle\Service\TaskQueueService;
use Released\QueueBundle\Tests\StubQueuedTask;
use Released\QueueBundle\Tests\StubTask;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskQueueServiceTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @expectedException \Released\QueueBundle\Exception\TaskAddException
     * @expectedExceptionMessage Type 'stub' not found
     */
    public function testShouldThrowTypeNotFoundException()
    {
        // GIVEN
        $task = new StubTask(['some data']);

        $container = new Container();
        /** @var QueuedTaskRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $this->getMockBuilder('Released\QueueBundle\Entity\QueuedTaskRepository')
            ->setMethods(['saveQueueTask', 'getQueuedTask', 'getQueuedTasksForRun', 'updateDependTasks'])
            ->disableOriginalConstructor()->getMock();

        $service = new TaskQueueService($container, $repository, []);

        // WHEN
        $service->addTask($task);
    }

    public function testShouldEnqueueTask()
    {
        // GIVEN
        /** @var StubTask|\PHPUnit_Framework_MockObject_MockObject $task */
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([['some data']])->setMethods(['beforeAdd', 'execute'])->getMock();
        $type = StubTask::getConfigType();

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $entity = new QueuedTask();
        $entity->setType($task->getType())
            ->setData($task->getData())
            ->setPriority($type->getPriority());

        $task->expects($this->once())->method('beforeAdd')
            ->with($container);

        $repository->expects($this->once())
            ->method('saveQueuedTask')->with($entity);

        $service = new TaskQueueService($container, $repository, ['stub' => $type], 'not used id');

        // WHEN
        $service->addTask($task);
    }

    public function testShouldEnqueueLocalTask()
    {
        // GIVEN
        /** @var StubTask|\PHPUnit_Framework_MockObject_MockObject $task */
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([['some data']])->setMethods(['beforeAdd', 'execute'])->getMock();
        $type = StubTask::getConfigType(true);

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $entity = new QueuedTask();
        $entity->setType($task->getType())
            ->setServer('server_id')
            ->setData($task->getData())
            ->setPriority($type->getPriority());

        $task->expects($this->once())->method('beforeAdd')
            ->with($container);

        $repository->expects($this->once())
            ->method('saveQueuedTask')->with($entity);

        $service = new TaskQueueService($container, $repository, ['stub' => $type], 'server_id');

        // WHEN
        $service->addTask($task);
    }

    public function testShouldExecuteTask()
    {
        // GIVEN
        $entity = new QueuedTask();
        /** @var \PHPUnit_Framework_MockObject_MockObject|StubTask $task */
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([['some data'], $entity])
            ->setMethods(['execute'])->getMock();

        $type = StubTask::getConfigType();

        $entity->setType($task->getType())
            ->setData($task->getData())
            ->setPriority($type->getPriority());

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $task->expects($this->once())->method('execute')
            ->willReturnCallback(function($container, TaskLoggerInterface $logger) use ($task) {
                $this->assertInstanceOf(ContainerInterface::class, $container);

                $logger->log($task, "Some log message");

                echo "Some raw output";
            });

        $repository->expects($this->once())
            ->method('setTaskStarted')->with($entity);

        $repository->expects($this->once())
            ->method('updateDependTasks')->with($entity);

        $expectedEntity = clone $entity;
        $expectedEntity->setState($entity::STATE_DONE)
            ->setFinishedAt(new \NoMSDateTime())
            ->setLog("[message]: Some log message
---
[info]: Some raw output
---
");

        $repository->expects($this->once())
            ->method('saveQueuedTask')->with($expectedEntity);

        $service = new TaskQueueService($container, $repository, ['stub' => $type]);

        // WHEN
        $service->executeTask($task);
    }

    public function testShouldRetryTask()
    {
        // GIVEN
        $entity = new QueuedTask();
        /** @var \PHPUnit_Framework_MockObject_MockObject|StubTask $task */
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([['some data'], $entity])
            ->setMethods(['execute'])->getMock();

        $type = StubTask::getConfigType();

        $entity->setType($task->getType())
            ->setData($task->getData())
            ->setPriority($type->getPriority());

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $task->expects($this->once())->method('execute')
            ->willThrowException(new TaskRetryException(120, "Some retry message"));

        $repository->expects($this->once())
            ->method('setTaskStarted')->with($entity);

        $expectedEntity = clone $entity;
        $expectedEntity->setState($entity::STATE_RETRY)
            ->setScheduledAt(new \NoMSDateTime("+120 seconds"))
            ->setFinishedAt(new \NoMSDateTime())
            ->setLog('[info]: 
---
[retry]: [Released\QueueBundle\Exception\TaskRetryException]: Some retry message
---
');

        $repository->expects($this->once())
            ->method('saveQueuedTask')->with($expectedEntity);

        $service = new TaskQueueService($container, $repository, ['stub' => $type]);

        // WHEN
        $service->executeTask($task);
    }

    public function testShouldNotRetryMoreThenOnce()
    {
        // GIVEN
        $entity = new QueuedTask();
        /** @var \PHPUnit_Framework_MockObject_MockObject|StubTask $task */
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([['some data'], $entity])
            ->setMethods(['execute'])->getMock();

        $type = StubTask::getConfigType();

        $entity->setType($task->getType())
            ->setState($entity::STATE_RETRY)
            ->setData($task->getData())
            ->setPriority($type->getPriority());

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $task->expects($this->once())->method('execute')
            ->willThrowException(new TaskRetryException(120, "Some retry message"));

        $repository->expects($this->once())
            ->method('setTaskStarted')->with($entity);

        $expectedEntity = clone $entity;
        $expectedEntity->setState($entity::STATE_FAIL)
            ->setFinishedAt(new \NoMSDateTime())
            ->setLog('[info]: 
---
[retry]: [Released\QueueBundle\Exception\TaskRetryException]: Some retry message
---
[fail]: Retry limit exceeded
---
');

        $repository->expects($this->once())
            ->method('saveQueuedTask')->with($expectedEntity);

        $service = new TaskQueueService($container, $repository, ['stub' => $type]);

        // WHEN
        $service->executeTask($task);
    }

    public function testShouldLogError()
    {
        // GIVEN
        $entity = new QueuedTask();
        /** @var \PHPUnit_Framework_MockObject_MockObject|StubTask $task */
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([['some data'], $entity])
            ->setMethods(['execute'])->getMock();

        $type = StubTask::getConfigType();

        $entity->setType($task->getType())
            ->setData($task->getData())
            ->setPriority($type->getPriority());

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $task->expects($this->once())->method('execute')
            ->willReturnCallback(function() {
                echo "Some output";

                throw new TaskExecutionException("Some exception raised");
            });

        $expectedEntity = clone $entity;
        $expectedEntity->setState($entity::STATE_FAIL)
            ->setFinishedAt(new \NoMSDateTime())
            ->setLog('[info]: Some output
---
[error]: [Released\QueueBundle\Exception\TaskExecutionException]: Some exception raised
---
');

        $repository->expects($this->once())
            ->method('saveQueuedTask')->with($expectedEntity);

        $service = new TaskQueueService($container, $repository, ['stub' => $type]);

        // WHEN
        $service->executeTask($task);
    }

    public function testShouldMapEntityToTask()
    {
        // GIVEN
        $type = StubTask::getConfigType();
        $entity = new StubQueuedTask(1, 'stub', ['some data']);

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $service = new TaskQueueService($container, $repository, ['stub' => $type]);

        // WHEN
        $task = $service->mapEntityToTask($entity);

        $expected = new StubTask(['some data'], $entity);
        $this->assertEquals($expected, $task);
    }

    /**
     * @expectedException \Released\QueueBundle\Exception\TaskAddException
     * @expectedExceptionMessage Type 'stub' not found
     */
    public function testShouldThrowExceptionOnMap()
    {
        // GIVEN
        StubTask::getConfigType();
        $entity = new StubQueuedTask(1, 'stub', ['some data']);

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $service = new TaskQueueService($container, $repository, []);

        // WHEN
        $service->mapEntityToTask($entity);
    }

    /**
     * @expectedException \Released\QueueBundle\Exception\TaskAddException
     * @expectedExceptionMessage Task class 'Released\QueueBundle\Tests\Service\TaskQueueServiceTest' must be subclass of Released\QueueBundle\Model\BaseTask
     */
    public function testShouldThrowExceptionInvalidSuperclass()
    {
        // GIVEN
        $type = new ConfigQueuedTaskType('stub', __CLASS__, 0);
        $entity = new StubQueuedTask(1, 'stub', ['some data']);

        $container = new Container();
        $repository = $this->getQueuedTypesRepositoryMock();

        $service = new TaskQueueService($container, $repository, ['stub' => $type]);

        // WHEN
        $service->mapEntityToTask($entity);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueuedTaskRepository
     */
    private function getQueuedTypesRepositoryMock()
    {
        $repository = $this->getMockBuilder('Released\QueueBundle\Entity\QueuedTaskRepository')
            ->setMethods(['saveQueuedTask', 'setTaskStarted', 'setTaskFinished', 'getQueuedTasksForRun', 'updateDependTasks'])
            ->disableOriginalConstructor()->getMock();
        return $repository;
    }

}
