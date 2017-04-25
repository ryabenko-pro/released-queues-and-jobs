<?php

namespace Released\QueueBundle\Events;

use Symfony\Component\EventDispatcher\Event;

class QueueExceptionEvent extends Event
{
    /** @var string */
    private $message;

    /**
     * QueueEvent constructor.
     * @param string $message
     */
    public function __construct($message)
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}