<?php


namespace Released\QueueBundle\Tests\Service;


use Psr\Log\NullLogger;
use Released\QueueBundle\Service\TaskSimpleExecutorService;
use Symfony\Component\DependencyInjection\Container;

class TaskSimpleExecutorServiceTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldExecuteTest()
    {
        // GIVEN
        $container = new Container();
        $executor = new TaskSimpleExecutorService($container, new NullLogger());

        // EXPECTED
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([[]])->setMethods(['beforeAdd', 'execute'])->getMock();

        $task->expects($this->once())->method('beforeAdd')->with($container);
        $task->expects($this->once())->method('execute')->with($container);

        $executor->addTask($task);
    }

}
