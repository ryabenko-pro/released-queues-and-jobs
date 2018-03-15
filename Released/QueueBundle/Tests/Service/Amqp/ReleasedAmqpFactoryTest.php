<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;
use Released\QueueBundle\Service\Amqp\MultiExchangeConsumer;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use Released\QueueBundle\Tests\StubTask;

class ReleasedAmqpFactoryTest extends TestCase
{
    /** @var AbstractConnection|MockObject */
    protected $connection;
    /** @var ReleasedAmqpFactory */
    protected $factory;
    /** @var AMQPChannel|MockObject */
    protected $channel;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->connection = $this->getMockBuilder(AbstractConnection::class)->disableOriginalConstructor()->getMock();
        $this->channel = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
        $this->connection->expects($this->any())->method('channel')->willReturn($this->channel);

        $this->factory = new ReleasedAmqpFactory($this->connection);
    }

    public function testShouldCreateProducer()
    {
        // WHEN
        $producer = $this->factory->getProducer($this->createTaskType('some.type_name'));

        // THEN
        $expected = new Producer($this->connection);
        $expected->setExchangeOptions([
            'name' => 'released.some_type__name',
            'type' => 'direct',
        ]);
        $expected->setQueueOptions([
            'name' => 'released.some_type__name',
        ]);

        $this->assertEquals($expected, $producer);
    }

    public function testShouldCreateProducerForLocalTask()
    {
        // WHEN
        $this->factory->setServerId('server_id');
        $producer = $this->factory->getProducer($this->createTaskType('some.type_name', true));

        // THEN
        $expected = new Producer($this->connection);
        $expected->setExchangeOptions([
            'name' => 'released.some_type__name.server_id',
            'type' => 'direct',
        ]);
        $expected->setQueueOptions([
            'name' => 'released.some_type__name.server_id',
        ]);

        $this->assertEquals($expected, $producer);
    }

    public function testShouldCreateConsumer()
    {
        // WHEN
        $consumer = $this->factory->getConsumer([$this->createTaskType('some.type_name')]);

        // THEN
        $expected = new MultiExchangeConsumer($this->connection, $this->channel);
        $expected->setExchangeOptions([
            'name' => '',
            'type' => 'direct',
        ]);
        $expected->setQueueOptions([
            'name' => '',
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
        ]);
        $expected->addQueue('released.some_type__name');

        $this->assertEquals($expected, $consumer);
    }

    public function testShouldCreateConsumerForLocalTask()
    {
        // WHEN
        $this->factory->setServerId('server_id');
        $consumer = $this->factory->getConsumer([$this->createTaskType('some.type_name', true)]);

        // THEN
        $expected = new MultiExchangeConsumer($this->connection, $this->channel);
        $expected->setExchangeOptions([
            'name' => '',
            'type' => 'direct',
        ]);
        $expected->setQueueOptions([
            'name' => '',
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
        ]);
        $expected->addQueue('released.some_type__name.server_id');

        $this->assertEquals($expected, $consumer);
    }

    public function testShouldApplyOptions()
    {
        // WHEN
        $factory = new ReleasedAmqpFactory(
            $this->connection,
            ['exchange_option' => '1', 'name' => 'not.relevant', 'type' => 'topic'],
            ['queue_option' => '1', 'name' => 'not.relevant', 'passive' => true, 'durable' => false,],
            'prefix'
        );
        $consumer = $factory->getConsumer([$this->createTaskType('some.type_name')]);

        // THEN
        $expected = new MultiExchangeConsumer($this->connection, $this->channel);
        $expected->setExchangeOptions([
            'name' => 'not.relevant',
            'type' => 'topic',
            'exchange_option' => '1',
        ]);
        $expected->setQueueOptions([
            'name' => 'not.relevant',
            'passive' => true,
            'durable' => false,
            'exclusive' => false,
            'auto_delete' => false,
            'queue_option' => '1',
        ]);
        $expected->addQueue('prefix.some_type__name');

        $this->assertEquals($expected, $consumer);
    }

    private function createTaskType($name, $isLocal = false) {
        return new ConfigQueuedTaskType($name, StubTask::class, 5, $isLocal);
    }
}
