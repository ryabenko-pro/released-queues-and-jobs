<?php

namespace Released\QueueBundle\Service\Logger;


use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TaskLoggerConsole implements TaskLoggerInterface
{

    /** @var OutputInterface */
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /** {@inheritdoc} */
    public function log(BaseTask $task, $message, $type = self::LOG_MESSAGE)
    {
        $this->output->writeln(sprintf("%s\t[%s]\t\t%s", date('Y-m-d H:i:s'), $type, $message));
    }
}