<?php

namespace Released\QueueBundle\Tests\Service\Amqp\Callback;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Service\Amqp\TaskQueueAmqpExecutor;
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

        $this->container = new Container();

        $this->message = $this->createMessage(['type' => 'test', 'data' => ['some' => 'data']]);
        $this->types['test'] = new ConfigQueuedTaskType('test', TrackedStubTask::class, 5);
        $this->types['next1'] = new ConfigQueuedTaskType('next1', TrackedStubTask::class, 5);
        $this->types['next2'] = new ConfigQueuedTaskType('next2', TrackedStubTask::class, 5);
        $this->types['next3'] = new ConfigQueuedTaskType('next3', TrackedStubTask::class, 5);

        $this->executor = new TaskQueueAmqpExecutor($this->factory, $this->container, $this->types);
        TrackedStubTask::$instances = [];
        TrackedStubTask::addMethodReturns('getType', 'test');
    }

    public function testShouldCreateTask()
    {
        // WHEN
        $this->executor->processMessage($this->message);

        // Then
        $this->assertEquals(1, count(TrackedStubTask::$instances), "Exactly 1 task must be created");

        $expected = new TrackedStubTask(['some' => 'data']);
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

    public function testShouldProcessFailedExecution()
    {
        // WHEN
        TrackedStubTask::addMethodReturns('execute', false);

        $result = $this->executor->processMessage($this->message);

        // THEN
        $this->assertFalse($result);
    }

    public function testShouldProcessValidExecution()
    {
        $this->factory->expects($this->at(0))->method('getProducer')->with($this->types['next1'])->willReturn($this->producer);
        $this->factory->expects($this->at(1))->method('getProducer')->with($this->types['next3'])->willReturn($this->producer);

        // EXPECTS
        $this->producer->expects($this->at(0))->method('publish')->with(serialize([
            'type' => 'next1',
            'data' => ['child' => '1'],
            'next' => [[
                'type' => 'next2',
                'data' => ['child' => '3'],
            ]]
        ]));

        $this->producer->expects($this->at(1))->method('publish')->with(serialize([
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

        $result = $this->executor->processMessage($message);


        // THEN
        $this->assertTrue($result);
    }

    /**
     * @param $payload
     * @return AMQPMessage
     */
    protected function createMessage($payload): AMQPMessage
    {
        return new AMQPMessage(serialize($payload));
    }
}







