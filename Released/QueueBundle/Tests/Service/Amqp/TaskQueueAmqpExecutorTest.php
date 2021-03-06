<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Exception\TaskRetryException;
use Released\QueueBundle\Service\Amqp\MessageUtil;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpExecutor;
use Released\QueueBundle\Service\EnqueuerInterface;
use Released\QueueBundle\Tests\Stub\TrackedStubTask;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaskQueueAmqpExecutorTest extends TestCase
{

    /** @var ConfigQueuedTaskType[] */
    protected $types;
    /** @var ContainerInterface */
    protected $container;
    /** @var AMQPMessage */
    protected $message;
    /** @var ReleasedAmqpFactory|MockObject */
    protected $factory;
    /** @var EnqueuerInterface|MockObject */
    protected $enqueuer;
    /** @var TaskQueueAmqpExecutor */
    protected $executor;
    /** @var ProducerInterface|MockObject */
    protected $producer;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->producer = $this->getMockBuilder(Producer::class)->disableOriginalConstructor()->getMock();

        $this->factory = $this->getMockBuilder(ReleasedAmqpFactory::class)->disableOriginalConstructor()->getMock();
        $this->enqueuer = $this->getMockBuilder(EnqueuerInterface::class)->getMock();

        $this->container = new Container();

        $this->message = $this->createMessage(['type' => 'test', 'data' => ['some' => 'data'], 'retry' => 2]);
        $this->types['test'] = new ConfigQueuedTaskType('test', TrackedStubTask::class, 5, false, 3);
        $this->types['next1'] = new ConfigQueuedTaskType('next1', TrackedStubTask::class, 5);
        $this->types['next2'] = new ConfigQueuedTaskType('next2', TrackedStubTask::class, 5);
        $this->types['next3'] = new ConfigQueuedTaskType('next3', TrackedStubTask::class, 5);

        $this->executor = new TaskQueueAmqpExecutor($this->factory, $this->enqueuer, $this->container, $this->types);
        TrackedStubTask::$instances = [];
        TrackedStubTask::addMethodReturns('getType', 'test');
    }

    public function testShouldCreateTaskObject()
    {
        // WHEN
        $this->executor->processMessage($this->message);

        // Then
        $this->assertEquals(1, count(TrackedStubTask::$instances), "Exactly 1 task must be created");

        $expected = new TrackedStubTask(['some' => 'data']);
        $expected->setRetries(2);

        $expected->execute($this->container, $this->executor);
        $this->assertEquals($expected, TrackedStubTask::$instances[0]);
    }

    public function testShouldExecuteTask()
    {
        // WHEN
        $this->executor->processMessage($this->message);

        // Then
        $this->assertEquals(1, count(TrackedStubTask::$instances), "Exactly 1 task must be created");
        $this->assertEquals(TrackedStubTask::$instances[0]->calls, [
            ['method' => 'execute', 'args' => [$this->container, $this->executor]]
        ]);
    }

    public function testShouldRetryFailed()
    {
        // EXPECTS
        $task = new TrackedStubTask(['some' => 'data']);
        $task->setRetries(2);

        $this->enqueuer->expects($this->once())->method('retry')
            ->with($this->createTasksCompareCallback($task));

        // WHEN
        TrackedStubTask::addMethodReturns('getType', 'test');
        TrackedStubTask::addMethodReturns('execute', false);

        $this->executor->processMessage($this->message);
    }

    public function testShouldRetryOnException()
    {
        // GIVEN
        $message = $this->createMessage([
            'type' => 'test',
            'data' => ['some' => 'data'],
            'next' => [[
                'type' => 'next2',
                'data' => ['child' => 'task'],
                'next' => [[
                    'type' => 'next3',
                    'data' => ['child' => 'another'],
                ]]
            ]]
        ]);

        // EXPECTS
        $task = new TrackedStubTask(['some' => 'data']);

        $next = new TrackedStubTask(['child' => 'task']);
        $next->addNextTask(new TrackedStubTask(['child' => 'another']));

        $task->addNextTask($next);

        $this->enqueuer->expects($this->once())->method('retry')
            ->with($this->createTasksCompareCallback($task));

        // WHEN
        TrackedStubTask::addMethodReturns('getType', 'test');
        TrackedStubTask::addMethodReturns('execute', function () {
            throw new \RuntimeException("Some runtime exception");
        });

        $this->executor->processMessage($message);
    }

    public function testShouldRetryWithChild()
    {
        // EXPECTS
        $task = new TrackedStubTask(['some' => 'data']);
        $task->setRetries(2);

        $this->enqueuer->expects($this->once())->method('retry')
            ->with($this->createTasksCompareCallback($task));

        // WHEN
        TrackedStubTask::addMethodReturns('getType', 'test');
        TrackedStubTask::addMethodReturns('execute', function () {
            throw new \RuntimeException("Some runtime exception");
        });

        $this->executor->processMessage($this->message);
    }

    public function testShouldNotRetryOnLimitReached()
    {
        // EXPECTS
        $payload = MessageUtil::unserialize($this->message->getBody());
        $payload['retry'] = 3;

        $message = $this->createMessage($payload);

        $this->factory->expects($this->never())->method('getProducer');

        // WHEN
        TrackedStubTask::addMethodReturns('execute', false);

        $this->executor->processMessage($message);
    }

    public function testShouldRetryTaskOnRetryException()
    {
        // EXPECTS
        $this->message = $this->createMessage(['type' => 'test', 'data' => ['some' => 'data'], 'retry' => 5]);

        $message = MessageUtil::unserialize($this->message->getBody());
        $message['retry'] = 6;

        $task = new TrackedStubTask(['some' => 'data']);
        $task->setRetries(5);
        TrackedStubTask::addMethodReturns('getType', 'test');

        $this->enqueuer->expects($this->once())->method('retry')
            ->with($this->createTasksCompareCallback($task));

        // WHEN
        TrackedStubTask::addMethodReturns('execute', function () {
            throw new TaskRetryException();
        });

        $this->executor->processMessage($this->message);
    }

    public function testShouldProcessValidExecution()
    {
        $this->factory->expects($this->at(0))->method('getProducer')->with($this->types['next1'])->willReturn($this->producer);
        $this->factory->expects($this->at(1))->method('getProducer')->with($this->types['next3'])->willReturn($this->producer);

        // EXPECTS
        $this->producer->expects($this->at(0))->method('publish')->with(MessageUtil::serialize([
            'type' => 'next1',
            'data' => ['child' => '1'],
            'next' => [[
                'type' => 'next2',
                'data' => ['child' => '3'],
            ]]
        ]));

        $this->producer->expects($this->at(1))->method('publish')->with(MessageUtil::serialize([
            'type' => 'next3',
            'data' => ['child' => '2'],
        ]));

        // WHEN
        TrackedStubTask::addMethodReturns('execute', true);

        $message = $this->createMessage([
            'type' => 'test',
            'data' => ['any' => 'data'],
            'next' => [[
                'type' => 'next1',
                'data' => ['child' => '1'],
                'next' => [
                    ['type' => 'next2', 'data' => ['child' => '3']],
                ]
            ], [
                'type' => 'next3',
                'data' => ['child' => '2'],
            ]]
        ]);

        $this->executor->processMessage($message);
    }

    /**
     * @param array $payload
     * @return AMQPMessage
     */
    protected function createMessage(array $payload): AMQPMessage
    {
        // TODO: create
        return new AMQPMessage(MessageUtil::serialize($payload));
    }

    /**
     * @param $task
     * @return Callback
     */
    protected function createTasksCompareCallback($task)
    {
        return $this->callback(function (TrackedStubTask $value) use ($task) {
            $value->calls = $task->calls;

            $this->assertEquals($task, $value);

            return true;
        });
    }
}







