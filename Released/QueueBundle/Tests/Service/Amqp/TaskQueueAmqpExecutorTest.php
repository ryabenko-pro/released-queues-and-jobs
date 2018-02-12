<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use PHPUnit\Framework\MockObject\MockObject;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Service\Amqp\MultiExchangeConsumer;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpExecutor;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\Tests\StubTask;
use Symfony\Component\DependencyInjection\Container;

class TaskQueueAmqpExecutorTest extends TestCase
{
    /** @var ConfigQueuedTaskType[] */
    protected $types = [];
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

        $this->types[] = new ConfigQueuedTaskType('stub', StubTask::class, 5);
        $this->types[] = new ConfigQueuedTaskType('test', StubTask::class, 5);

        $this->executor = new TaskQueueAmqpExecutor($this->factory, $this->container, $this->types);
    }

    public function testShouldBindToExchanges()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(MultiExchangeConsumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with($this->types)->willReturn($consumerStub);

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
        $consumerStub = $this->getMockBuilder(MultiExchangeConsumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with([$this->types[0]])->willReturn($consumerStub);

        // WHEN
        $this->executor->runTasks(['stub']);
    }
    public function testShouldSkipSelected()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(MultiExchangeConsumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with([$this->types[1]])->willReturn($consumerStub);

        // WHEN
        $this->executor->runTasks(null, ['stub']);
    }


    public function testShouldRunTask()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(MultiExchangeConsumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with([$this->types[0]])->willReturn($consumerStub);

        // WHEN
        $this->executor->runTasks(['stub']);
    }

}
