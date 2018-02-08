<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Released\QueueBundle\Service\Amqp\ReleasedAmqpFactory;
use PHPUnit\Framework\TestCase;
use Released\QueueBundle\Tests\Stub\StubAmqpConnection;

class ReleasedAmqpFactoryTest extends TestCase
{
    /** @var StubAmqpConnection */
    protected $connection;
    /** @var ReleasedAmqpFactory */
    protected $factory;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        $this->connection = new StubAmqpConnection();
        $this->factory = new ReleasedAmqpFactory($this->connection);
    }

    public function testShouldCreateProducer()
    {
        // WHEN
        $producer = $this->factory->getProducer('some.type_name');

        // THEN
        $expected = new Producer($this->connection);
        $expected->setExchangeOptions([
            'name' => 'released.some_type__name',
            'type' => 'direct',
        ]);

        $this->assertEquals($expected, $producer);
    }

    public function testShouldCreateConsumer()
    {
        // WHEN
        $producer = $this->factory->getConsumer('some.type_name');

        // THEN
        $expected = new Consumer($this->connection);
        $expected->setExchangeOptions([
            'name' => 'released.some_type__name',
            'type' => 'direct',
        ]);
        $expected->setQueueOptions([
            'name' => 'released.some_type__name_' . getmypid(),
            'passive' => false,
            'durable' => true,
            'exclusive' => true,
            'auto_delete' => true,
        ]);

        $this->assertEquals($expected, $producer);
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
        $producer = $factory->getConsumer('some.type_name');

        // THEN
        $expected = new Consumer($this->connection);
        $expected->setExchangeOptions([
            'name' => 'prefix.some_type__name',
            'type' => 'direct',
            'exchange_option' => '1',
        ]);
        $expected->setQueueOptions([
            'name' => 'prefix.some_type__name_' . getmypid(),
            'passive' => true,
            'durable' => false,
            'exclusive' => true,
            'auto_delete' => true,
            'queue_option' => '1',
        ]);

        $this->assertEquals($expected, $producer);
    }
}
