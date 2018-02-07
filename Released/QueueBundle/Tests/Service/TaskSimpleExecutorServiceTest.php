<?php


namespace Released\QueueBundle\Tests\Service;


use Psr\Log\NullLogger;
use Released\QueueBundle\Service\Inline\InlineEnqueuerService;
use Symfony\Component\DependencyInjection\Container;

class TaskSimpleExecutorServiceTest extends \PHPUnit_Framework_TestCase
{

    public function testShouldExecuteTest()
    {
        // GIVEN
        $container = new Container();
        $executor = new InlineEnqueuerService($container, new NullLogger());

        // EXPECTED
        $task = $this->getMockBuilder('Released\QueueBundle\Tests\StubTask')
            ->setConstructorArgs([[]])->setMethods(['beforeAdd', 'execute'])->getMock();

        $task->expects($this->once())->method('beforeAdd')->with($container);
        $task->expects($this->once())->method('execute')->with($container);

        $executor->enqueue($task);
    }

}
