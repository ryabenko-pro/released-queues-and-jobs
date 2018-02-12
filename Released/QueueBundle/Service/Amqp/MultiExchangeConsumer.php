<?php


namespace Released\QueueBundle\Service\Amqp;


use OldSound\RabbitMqBundle\RabbitMq\Consumer;
use PhpAmqpLib\Message\AMQPMessage;

class MultiExchangeConsumer extends Consumer
{

    protected $exchangeDeclared = true;

    protected $queues = [];

    /**
     * @param array $options
     * @return array Resulting options
     */
    public function setQueueOptions(array $options = array())
    {
        parent::setQueueOptions($options);

        return $this->queueOptions;
    }

    public function addExchange($exchangeName)
    {
        $this->getChannel()->exchange_declare(
            $exchangeName,
            $this->exchangeOptions['type'],
            $this->exchangeOptions['passive'],
            $this->exchangeOptions['durable'],
            $this->exchangeOptions['auto_delete'],
            $this->exchangeOptions['internal'],
            $this->exchangeOptions['nowait'],
            $this->exchangeOptions['arguments'],
            $this->exchangeOptions['ticket']
        );
    }

    public function addQueue($queueName)
    {
        $this->queues[] = $queueName;

        $this->getChannel()->queue_declare(
            $queueName,
            $this->queueOptions['passive'],
            $this->queueOptions['durable'],
            $this->queueOptions['exclusive'],
            $this->queueOptions['auto_delete'],
            $this->queueOptions['nowait'],
            $this->queueOptions['arguments'],
            $this->queueOptions['ticket']
        );
    }

    protected function setupConsumer()
    {
        // Do nothing as everything is set up already
    }

    /**
     * @inheritDoc
     */
    public function stopConsuming()
    {
        foreach ($this->queues as $name) {
            $this->getChannel()->basic_cancel($this->getQueueConsumerTag($name), false, true);
        }
    }

    /**
     * @param $queueName
     * @param $exchangeName
     */
    public function bindAndConsume($queueName, $exchangeName)
    {
        $this->queueBind($queueName, $exchangeName, '');

        $this->getChannel()->basic_consume($queueName, $this->getQueueConsumerTag($queueName), false, false, false, false, function(AMQPMessage $msg) use ($queueName) {
            $this->processMessageQueueCallback($msg, $queueName, $this->callback);
        });
    }

    public function getQueueConsumerTag($queue)
    {
        return sprintf('%s-%s', $this->getConsumerTag(), $queue);
    }

    /**
     * @param int $target
     * @return self
     */
    public function setMessagesLimit($target)
    {
        $this->target = $target;

        return $this;
    }

}