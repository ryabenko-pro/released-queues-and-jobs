<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Service\Amqp\MessageUtil;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpEnqueuer;
use Released\QueueBundle\Tests\Stub\StubProducer;
use Released\QueueBundle\Tests\StubTask;

class TaskQueueAmqpEnqueuerTest extends TestCase
{

    /** @var TaskQueueAmqpEnqueuer|MockObject */
    protected $service;
    /** @var ReleasedAmqpFactory|MockObject */
    protected $factory;
    /** @var StubProducer|MockObject */
    protected $producer;
    /** @var ConfigQueuedTaskType */
    protected $type;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(ReleasedAmqpFactory::class)->disableOriginalConstructor()->getMock();
        $this->producer = $this->getMockBuilder(StubProducer::class)->getMock();
        $this->type = new ConfigQueuedTaskType('stub', StubTask::class, 5);

        $this->service = new TaskQueueAmqpEnqueuer($this->factory, [$this->type]);
    }

    public function testShouldPublishMessage()
    {
        // EXPECTS
        $this->factory->expects($this->once())->method('getProducer')
            ->with($this->type)->willReturn($this->producer);

        $this->producer->expects($this->once())->method('publish')
            ->with(MessageUtil::serialize(['type' => 'stub', 'data' => ['some' => 'data']]));

        // WHEN
        $task = new StubTask(['some' => 'data']);
        $this->service->enqueue($task);
    }

    public function testShouldPublishMessagesChain()
    {
        // GIVEN
        $task = new StubTask(['some' => 'data']);
        $task->addNextTask(new StubTask(['child' => 1]));
        $task->addNextTask(new StubTask(['child' => 2]));
        $parent = new StubTask(['parent' => 'task']);
        $parent->addNextTask($task);

        // EXPECTS
        $this->factory->expects($this->once())->method('getProducer')
            ->with($this->type)->willReturn($this->producer);

        $this->producer->expects($this->once())->method('publish')
            ->with(MessageUtil::serialize([
                'type' => 'stub',
                'data' => ['parent' => 'task'],
                'next' => [[
                    'type' => 'stub',
                    'data' => ['some' => 'data'],
                    'next' => [
                        ['type' => 'stub', 'data' => ['child' => 1]],
                        ['type' => 'stub', 'data' => ['child' => 2]],
                    ]
                ]]
            ]));

        // WHEN
        $this->service->enqueue($parent);
    }

    public function testShouldRetryTask()
    {
        // GIVEN
        $task = new StubTask(['some' => 'data']);

        // EXPECTS
        $this->factory->expects($this->once())->method('getProducer')
            ->with($this->type)->willReturn($this->producer);

        $this->producer->expects($this->once())->method('publish')
            ->with(MessageUtil::serialize([
                'type' => 'stub',
                'data' => ['some' => 'data'],
                'retry' => $task->getRetries() + 1,
            ]));

        // WHEN
        $this->service->retry($task);
    }
}
