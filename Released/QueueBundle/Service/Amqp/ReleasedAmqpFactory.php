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

        return $producer;
    }

    /**
     * @param ConfigQueuedTaskType[] $types
     * @return MultiExchangeConsumer
     */
    public function getConsumer($types): MultiExchangeConsumer
    {
        $instance = new MultiExchangeConsumer($this->conn);

        $instance->setExchangeOptions(array_merge(
            ['name' => '', 'type' => 'direct'],
            $this->exchangeOptions
        ));

        $instance->setQueueOptions(array_merge([
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => true,
        ], $this->queueOptions));

        foreach ($types as $type) {
            $exchangeName = $this->getExchangeName($type->getName());

            $queueName = $exchangeName;
            $instance->addQueue($queueName);
            $instance->addExchange($exchangeName);

            $instance->bindAndConsume($queueName, $exchangeName);
        }

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