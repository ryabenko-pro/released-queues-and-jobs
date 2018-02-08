<?php


namespace Released\QueueBundle\Tests\Stub;


use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class StubProducer implements ProducerInterface
{

    /** {@inheritdoc} */
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {

    }
}