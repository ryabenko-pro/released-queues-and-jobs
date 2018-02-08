<?php


namespace Released\QueueBundle\Service\Amqp;


use OldSound\RabbitMqBundle\RabbitMq\Consumer;
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
    /** @var array */
    protected $queueOptions;
    /** @var array */
    protected $exchangeOptions;

    public function __construct(AbstractConnection $conn, $exchangeOptions = [], $queueOptions = [], $exchangePrefix = 'released')
    {
        $this->exchangeOptions = (array)$exchangeOptions;
        $this->queueOptions = (array)$queueOptions;
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

        $producer->setExchangeOptions($this->getExchangeOptions($type));

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
     * @return Consumer
     */
    public function getConsumer(string $type): Consumer
    {
        $instance = new Consumer($this->conn);

        $instance->setExchangeOptions($this->getExchangeOptions($type));

        $options = array_merge([
            'passive' => false,
            'durable' => true,
            'exclusive' => true,
            'auto_delete' => true,
        ], $this->queueOptions);

        $options['name'] = sprintf('%s_%d', $this->getExchangeName($type), getmypid());

        $instance->setQueueOptions($options);

/*
        if ($this->has('debug.event_dispatcher')) {
            $instance->setEventDispatcher(${($_ = isset($this->services['debug.event_dispatcher']) ? $this->services['debug.event_dispatcher'] : $this->get('debug.event_dispatcher', ContainerInterface::NULL_ON_INVALID_REFERENCE)) && false ?: '_'});
        }
*/

        return $instance;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getExchangeName(string $type): string
    {
        $type = strtr($type, ['_' => '__', '.' => '_']);

        return sprintf('%s.%s', $this->exchangePrefix, $type);
    }

    /**
     * @param string $type
     * @return array
     */
    protected function getExchangeOptions(string $type): array
    {
        return array_merge($this->exchangeOptions, [
            'name' => $this->getExchangeName($type),
            'type' => 'direct',
        ]);
    }

}