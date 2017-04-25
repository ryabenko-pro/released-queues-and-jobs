<?php

namespace Released\QueueBundle\Tests;


use Released\QueueBundle\Entity\QueuedTask;

class StubQueuedTask extends QueuedTask
{

    function __construct($id, $type = null, $data = null)
    {
        $this->id = $id;
        $this->type = $type;
        $this->data = $data;
    }

}