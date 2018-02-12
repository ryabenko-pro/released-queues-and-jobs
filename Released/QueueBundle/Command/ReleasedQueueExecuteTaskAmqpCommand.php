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
        if ($input->getOption('permanent')) {
            $output->writeln("Option `permanent` is ignored for amqp command");
        }

        if ($input->getOption('time-limit')) {
            $output->writeln("Option `time-limit` is ignored for amqp command");
        }

        $verbose = $input->getOption('verbose');
        $logger = $verbose ? new TaskLoggerConsole($output) : null;

        $service = $this->getContainer()->get('released.queue.task_queue.service_amqp_executor');

        $types = $input->getOption('type');
        $noTypes = $input->getOption('no-type');

        $memory = $input->getOption("memory-limit");
        if (!is_null($memory)) {
            $service->setMemoryLimit(intval($memory) / (1024 * 1024));
        }

        $messages = $input->getOption("cycles-limit");
        if (!is_null($messages)) {
            $service->setMessagesLimit(intval($messages));
        }

        $service->runTasks($types, $noTypes, $logger);

        return false;
    }

}
