<?php


namespace Released\QueueBundle\Service\Amqp;


use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use PhpAmqpLib\Connection\AbstractConnection;
use Released\QueueBundle\DependencyInjection\Util\ConfigQueuedTaskType;

class ReleasedAmqpFactory
{

    /** @var ConfigQueuedTaskType[] */
    protected $types;
    /** @var AbstractConnection */
    protected $conn;
    /** @var string */
    protected $exchangePrefix;

    /**
     * @param AbstractConnection $conn
     * @param string $exchangePrefix
     */
    public function __construct(AbstractConnection $conn, $exchangePrefix = 'released.')
    {
        $this->conn = $conn;
        $this->exchangePrefix = $exchangePrefix;
    }

    /**
     * @param string $type
     * @return ProducerInterface
     */
    public function getProducer(string $type): ProducerInterface
    {
        $producer = new Producer($this->conn);

        $producer->setExchangeOptions([
            'name' => $this->getExchangeName($type),
            'type' => 'direct',
        ]);

/*
        // Copied from generated container
        $instance->setQueueOptions(['name' => '', 'declare' => false]);

        if ($this->has('debug.event_dispatcher')) {
            $instance->setEventDispatcher(${($_ = isset($this->services['debug.event_dispatcher']) ? $this->services['debug.event_dispatcher'] : $this->get('debug.event_dispatcher', ContainerInterface::NULL_ON_INVALID_REFERENCE)) && false ?: '_'});
        }
*/

        return $producer;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getExchangeName(string $type): string
    {
        $type = strtr($type, ['_' => '__', '.' => '_']);

        return $this->exchangePrefix . $type;
    }

}