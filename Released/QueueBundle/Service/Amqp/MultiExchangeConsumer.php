<?php


namespace Released\QueueBundle\Service\Amqp;


use OldSound\RabbitMqBundle\RabbitMq\Consumer;

class MultiExchangeConsumer extends Consumer
{

    protected $exchangeDeclared = true;

    /**
     * @param array $options
     * @return array Resulting options
     */
    public function setExchangeOptions(array $options = array())
    {
        parent::setExchangeOptions($options);

        return $this->exchangeOptions;
    }

    /**
     * @param array $options
     * @return array Resulting options
     */
    public function setQueueOptions(array $options = array())
    {
        parent::setQueueOptions($options);

        return $this->queueOptions;
    }

    /**
     * @param $queue
     * @param $exchange
     */
    public function bind($queue, $exchange)
    {
        $this->queueBind($queue, $exchange, '');
    }

}