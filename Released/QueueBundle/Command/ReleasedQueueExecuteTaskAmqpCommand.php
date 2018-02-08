<?php

namespace Released\QueueBundle\Command;

use Released\QueueBundle\Service\Logger\TaskLoggerConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ReleasedQueueExecuteTaskAmqpCommand extends BaseReleasedQueueExecuteTaskCommand
{
    protected function configure()
    {
        $this->setName("released:queue:execute-task-amqp")
            ->setAliases(["released:queue:amqp-execute-task"]);
    }

    /**
     * {@inheritdoc}
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $verbose = $input->getOption('verbose');
        $logger = $verbose ? new TaskLoggerConsole($output) : null;

        $service = $this->getContainer()->get('released.queue.task_queue.service_amqp_executor');

        $types = $input->getOption('type');
        $noTypes = $input->getOption('no-type');
        $service->runTasks($types, $noTypes, $logger);

        return true;
    }

}
