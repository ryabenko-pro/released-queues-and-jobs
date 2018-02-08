<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use PHPUnit\Framework\MockObject\MockObject;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpEnqueuer;
use PHPUnit\Framework\TestCase;
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

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(ReleasedAmqpFactory::class)->disableOriginalConstructor()->getMock();
        $this->producer = $this->getMockBuilder(StubProducer::class)->getMock();
        $this->service = new TaskQueueAmqpEnqueuer($this->factory);
    }

    public function testShouldPublishMessage()
    {
        // EXPECTS
        $this->factory->expects($this->once())->method('getProducer')
            ->with('stub')->willReturn($this->producer);

        $this->producer->expects($this->once())->method('publish')
            ->with(serialize(['type' => 'stub', 'data' => ['some' => 'data']]));

        // WHEN
        $task = new StubTask(['some' => 'data']);
        $this->service->enqueue($task);
    }

    public function testShouldPublishMessagesChain()
    {
        // EXPECTS
        $this->factory->expects($this->once())->method('getProducer')
            ->with('stub')->willReturn($this->producer);

        $this->producer->expects($this->once())->method('publish')
            ->with(serialize([
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
        $task = new StubTask(['some' => 'data']);
        $task->addNextTask(new StubTask(['child' => 1]));
        $task->addNextTask(new StubTask(['child' => 2]));

        $parent = new StubTask(['parent' => 'task']);
        $parent->addNextTask($task);

        $this->service->enqueue($parent);
    }
}
