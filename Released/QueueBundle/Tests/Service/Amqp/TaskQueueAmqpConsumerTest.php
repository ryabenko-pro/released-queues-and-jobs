<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use PHPUnit\Framework\MockObject\MockObject;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Service\Amqp\MessageUtil;
use Released\QueueBundle\Service\Amqp\MultiExchangeConsumer;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpConsumer;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpExecutor;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\Service\Db\TaskQueueDbService;
use Released\QueueBundle\Service\EnqueuerInterface;
use Released\QueueBundle\Tests\StubTask;
use Released\QueueBundle\Tests\StubTaskLogger;
use Symfony\Component\DependencyInjection\Container;

class TaskQueueAmqpConsumerTest extends TestCase
{
    /** @var ConfigQueuedTaskType[] */
    protected $types = [];
    /** @var ReleasedAmqpFactory|MockObject */
    protected $factory;
    /** @var EnqueuerInterface|MockObject */
    protected $enqueuer;
    /** @var TaskQueueAmqpExecutor|MockObject */
    protected $executor;
    /** @var TaskQueueDbService|MockObject */
    protected $dbService;
    /** @var Container */
    protected $container;

    /** @var TaskQueueAmqpConsumer */
    protected $consumer;

    /** @inheritDoc */
    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(ReleasedAmqpFactory::class)->disableOriginalConstructor()->getMock();
        $this->enqueuer = $this->getMockBuilder(EnqueuerInterface::class)->getMock();
        $this->executor = $this->getMockBuilder(TaskQueueAmqpExecutor::class)->disableOriginalConstructor()->getMock();
        $this->dbService = $this->getMockBuilder(TaskQueueDbService::class)->disableOriginalConstructor()->getMock();
        $this->container = new Container();

        $this->types[] = new ConfigQueuedTaskType('stub', StubTask::class, 5);
        $this->types[] = new ConfigQueuedTaskType('test', StubTask::class, 5);

        $this->consumer = new TaskQueueAmqpConsumer($this->factory, $this->executor, $this->dbService, $this->types);
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
        $this->consumer->runTasks();
    }

    public function testShouldRunSelected()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(MultiExchangeConsumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with([$this->types[0]])->willReturn($consumerStub);

        // WHEN
        $this->consumer->runTasks(['stub']);
    }
    public function testShouldSkipSelected()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(MultiExchangeConsumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with([$this->types[1]])->willReturn($consumerStub);

        // WHEN
        $this->consumer->runTasks(null, ['stub']);
    }


    public function testShouldRunTask()
    {
        // EXPECT
        $consumerStub = $this->getMockBuilder(MultiExchangeConsumer::class)->disableOriginalConstructor()->getMock();
        $this->factory->expects($this->once())->method('getConsumer')
            ->with([$this->types[0]])->willReturn($consumerStub);

        // WHEN
        $this->consumer->runTasks(['stub']);
    }

    public function testShouldProcessSimpleMessage()
    {
        // GIVEN
        $message = MessageUtil::createMessage([
            'type' => 'test',
            'data' => ['some' => 'data'],
        ]);

        $logger = new StubTaskLogger();

        // EXPECT
        $this->dbService->expects($this->never())->method('runTaskById');

        $this->executor->expects($this->once())->method('processMessage')
            ->with($message, $logger);

        // WHEN
        $this->consumer->processMessage($message, $logger);
    }

    public function testShouldProcessMixedMessage()
    {
        // GIVEN
        $message = MessageUtil::createMessage([
            'task_id' => 123,
        ]);

        $logger = new StubTaskLogger();

        // EXPECT
        $this->dbService->expects($this->once())->method('runTaskById')
            ->with(123);

        $this->executor->expects($this->never())->method('processMessage');

        // WHEN
        $this->consumer->processMessage($message, $logger);
    }
}
