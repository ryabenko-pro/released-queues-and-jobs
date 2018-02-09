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
     * @param ConfigQueuedTaskType[] $types
     * @return Consumer
     */
    public function getConsumer($types): Consumer
    {
        $instance = new MultiExchangeConsumer($this->conn);

        $exchangeOptions = $instance->setExchangeOptions(array_merge(['name' => '', 'type' => 'direct'], $this->exchangeOptions));

        foreach ($types as $type) {
            $exchangeName = $this->getExchangeName($type->getName());

            $queueOptions = $instance->setQueueOptions(array_merge([
                'passive' => false,
                'durable' => true,
                'exclusive' => false,
                'auto_delete' => true,
            ], $this->queueOptions));

//            $queueName = sprintf('%s_%d', $this->exchangePrefix, getmypid());
            $queueName = $exchangeName;
            $instance->getChannel()->queue_declare(
                $queueName,
                $queueOptions['passive'],
                $queueOptions['durable'],
                $queueOptions['exclusive'],
                $queueOptions['auto_delete'],
                $queueOptions['nowait'],
                $queueOptions['arguments'],
                $queueOptions['ticket']
            );

            $instance->getChannel()->exchange_declare(
                $exchangeName,
                $exchangeOptions['type'],
                $exchangeOptions['passive'],
                $exchangeOptions['durable'],
                $exchangeOptions['auto_delete'],
                $exchangeOptions['internal'],
                $exchangeOptions['nowait'],
                $exchangeOptions['arguments'],
                $exchangeOptions['ticket']
            );

            $instance->bind($queueName, $exchangeName);

//            $instance->getChannel()->basic_consume($queueName, $instance->getConsumerTag(), false, false, false, false, array($instance, 'processMessage'));
            $instance->getChannel()->basic_consume($queueName, '', false, false, false, false, array($instance, 'processMessage'));


//            if (isset($this->queueOptions['routing_keys']) && count($this->queueOptions['routing_keys']) > 0) {
//                foreach ($this->queueOptions['routing_keys'] as $routingKey) {
//                    $this->queueBind($queueName, $this->exchangeOptions['name'], $routingKey);
//                }
//            } else {
//            }
        }

//        $instance->getChannel()->basic_consume($queueName, $queueName, false, false, false, false, function (AMQPMessage $msg) use($queueName, $instance) {
//            $instance->processMessage($msg);
//        });

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