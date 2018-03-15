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
    /** @var ProducerInterface[] */
    protected $producers = [];
    /** @var string|null */
    protected $serverId;

    public function __construct(AbstractConnection $conn, $exchangeOptions = [], $queueOptions = [], $exchangePrefix = 'released')
    {
        $this->exchangeOptions = (array)$exchangeOptions;
        $this->queueOptions = (array)$queueOptions;
        $this->conn = $conn;
        $this->exchangePrefix = $exchangePrefix;
    }

    /**
     * @param string $type
     * @param bool $isLocal
     * @return ProducerInterface
     */
    public function getProducer(string $type, $isLocal = false): ProducerInterface
    {
        if (!isset($this->producers[$type])) {
            $producer = new Producer($this->conn);

            $producer->setExchangeOptions($this->getExchangeOptions($type, $isLocal));

            $this->producers[$type] = $producer;
        }

        return $this->producers[$type];
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
            'auto_delete' => false,
        ], $this->queueOptions));

        foreach ($types as $type) {
            $exchangeName = $this->getExchangeName($type->getName(), $type->isLocal());

            $queueName = $exchangeName;
            $instance->addQueue($queueName);
            $instance->addExchange($exchangeName);

            $instance->bindAndConsume($queueName, $exchangeName);
        }

        return $instance;
    }

    /**
     * @param string $type
     * @param bool $isLocal
     * @return string
     */
    protected function getExchangeName(string $type, $isLocal = false): string
    {
        $type = strtr($type, ['_' => '__', '.' => '_']);

        return sprintf('%s.%s%s', $this->exchangePrefix, $type, $isLocal ? '.' . $this->serverId : '');
    }

    /**
     * @param string $type
     * @param bool $isLocal
     * @return array
     */
    protected function getExchangeOptions(string $type, $isLocal = false): array
    {
        return array_merge($this->exchangeOptions, [
            'name' => $this->getExchangeName($type, $isLocal),
            'type' => 'direct',
        ]);
    }

    /**
     * @param string $serverId
     * @return self
     */
    public function setServerId($serverId)
    {
        $this->serverId = $serverId;
        return $this;
    }
}