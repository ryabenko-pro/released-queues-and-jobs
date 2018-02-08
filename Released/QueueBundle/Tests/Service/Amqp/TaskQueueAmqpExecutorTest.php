<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PHPUnit\Framework\MockObject\MockObject;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpExecutor;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\Tests\StubTask;
use Symfony\Component\DependencyInjection\Container;

class TaskQueueAmqpExecutorTest extends TestCase
{

    /** @var ReleasedAmqpFactory|MockObject */
    protected $factory;
    /** @var Container */
    protected $container;
    /** @var TaskQueueAmqpExecutor */
    protected $executor;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(ReleasedAmqpFactory::class)->disableOriginalConstructor()->getMock();
        $this->container = new Container();

        $types = [];
        $types[] = new ConfigQueuedTaskType('stub', StubTask::class, 5);
        $types[] = new ConfigQueuedTaskType('test', StubTask::class, 5);

        $this->executor = new TaskQueueAmqpExecutor($this->factory, $this->container, $types);
    }

    public function testShouldBindToExchanges()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(Consumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->at(0))->method('getConsumer')
            ->with('stub')->willReturn($consumerStub);

        $consumerStub->expects($this->once())->method('setCallback')
            ->with($this->callback(function ($value) {
                return is_callable($value);
            }));

        $consumerTest = $this->getMockBuilder(Consumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->at(1))->method('getConsumer')
            ->with('test')->willReturn($consumerTest);

        $consumerStub->expects($this->once())->method('setCallback')
            ->with($this->callback(function ($value) {
                return is_callable($value);
            }));

        // WHEN
        $this->executor->runTasks();
    }

    public function testShouldRunSelected()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(Consumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with('stub')->willReturn($consumerStub);

        // WHEN
        $this->executor->runTasks(['stub']);
    }
    public function testShouldSkipSelected()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(Consumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with('test')->willReturn($consumerStub);

        // WHEN
        $this->executor->runTasks(null, ['stub']);
    }


    public function testShouldRunTask()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(Consumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with('stub')->willReturn($consumerStub);

        // WHEN
        $this->executor->runTasks(['stub']);
    }

}
