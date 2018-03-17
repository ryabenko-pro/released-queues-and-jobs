<?php


namespace Released\QueueBundle\Service\Amqp;


use PhpAmqpLib\Message\AMQPMessage;

abstract class MessageUtil
{

    /**
     * @param array $message
     * @return string
     */
    static public function serialize($message)
    {
        return json_encode($message, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string $body
     * @return array
     */
    public static function unserialize($body)
    {
        return json_decode($body, true);
    }

    /**
     * @param string|array $payload
     * @return AMQPMessage
     */
    static public function createMessage($payload): AMQPMessage
    {
        if (is_array($payload)) {
            $payload = self::serialize($payload);
        }

        return new AMQPMessage($payload);
    }

}