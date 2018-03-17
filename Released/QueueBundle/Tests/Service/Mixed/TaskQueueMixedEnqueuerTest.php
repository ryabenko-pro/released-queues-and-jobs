<?php

namespace Released\QueueBundle\Service\Mixed;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpEnqueuer;
use Released\QueueBundle\Service\Db\TaskQueueDbService;
use Released\QueueBundle\Tests\StubTask;

class TaskQueueMixedEnqueuerTest extends TestCase
{

    public function testShouldEnqueueTask()
    {
        // GIVEN
        $data = ['some' => 'data'];
        $task = new StubTask($data);

        /** @var TaskQueueDbService|MockObject $db */
        $db = $this->getMockBuilder(TaskQueueDbService::class)->disableOriginalConstructor()->getMock();
        /** @var TaskQueueAmqpEnqueuer|MockObject $amqp */
        $amqp = $this->getMockBuilder(TaskQueueAmqpEnqueuer::class)->disableOriginalConstructor()->getMock();

        $service = new TaskQueueMixedEnqueuer($db, $amqp);

        // EXPECTS
        $db->expects($this->once())->method('enqueue')
            ->with($task)->willReturn(111);

        $amqp->expects($this->once())->method('enqueue')
            ->with($task)->willReturn(0);

        // WHEN
        $service->enqueue($task);
    }

    public function testShouldRretryTask()
    {
        // GIVEN
        $data = ['some' => 'data'];
        $task = new StubTask($data);

        /** @var TaskQueueDbService|MockObject $db */
        $db = $this->getMockBuilder(TaskQueueDbService::class)->disableOriginalConstructor()->getMock();
        /** @var TaskQueueAmqpEnqueuer|MockObject $amqp */
        $amqp = $this->getMockBuilder(TaskQueueAmqpEnqueuer::class)->disableOriginalConstructor()->getMock();

        $service = new TaskQueueMixedEnqueuer($db, $amqp);

        // EXPECTS
        $db->expects($this->once())->method('retry')
            ->with($task)->willReturn(111);

        $amqp->expects($this->once())->method('retry')
            ->with($task)->willReturn(0);

        // WHEN
        $service->retry($task);
    }

}
