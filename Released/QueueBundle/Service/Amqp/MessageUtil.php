<?php


namespace Released\QueueBundle\Service\Amqp;


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

}