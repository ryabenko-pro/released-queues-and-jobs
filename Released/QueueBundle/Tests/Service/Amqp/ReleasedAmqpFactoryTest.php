<?php

namespace Released\QueueBundle\Tests\Service\Amqp;

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

}
