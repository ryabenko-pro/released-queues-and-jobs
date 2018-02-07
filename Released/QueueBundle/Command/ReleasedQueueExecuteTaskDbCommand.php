<?php

namespace Released\QueueBundle\Command;

use Released\QueueBundle\Service\Logger\TaskLoggerConsole;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ReleasedQueueExecuteTaskDbCommand extends BaseReleasedQueueExecuteTaskCommand
{
    protected function configure()
    {
        $this->setName("released:queue:execute-task")
            ->addOption("ask", "a", InputOption::VALUE_NONE, "If present ask for task IDs to execute")
            ->addArgument('ids', InputArgument::OPTIONAL|InputArgument::IS_ARRAY, 'Run tasks with specific id, regardless ist state.');
    }

    /**
     * {@inheritdoc}
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $ids = $this->getIds($input, $output);
        if (['q'] === $ids || ['quit'] === $ids) {
            return false;
        }

        $verbose = $input->getOption('verbose');
        $logger = $verbose ? new TaskLoggerConsole($output) : null;

        $service = $this->getContainer()->get('released.queue.task_queue.service_database');

        if (empty($ids)) {
            $types = $input->getOption('type');
            $noTypes = $input->getOption('no-type');
            $service->runTasks($types, $noTypes);
        } else {
            if ($input->getOption('permanent') && !$input->getOption('ask')) {
                throw new \Exception("Ids can not be used with 'permanent' option");
            }

            $entities = $this->getContainer()->get('released.queue.repository.queued_task')->findByIds($ids);
            foreach ($entities as $entity) {
                $task = $service->mapEntityToTask($entity);
                $service->executeTask($task, $logger);
            }
        }

        return true;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int[]
     */
    protected function getIds(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getArgument('ids');

        if ($input->getOption('ask')) {
            $helper = $this->getHelper('question');
            $question = new Question("Please enter task IDs separated by spaces ('q' or 'quit' to exit): ");

            $interactiveIds = $helper->ask($input, $output, $question);
            $interactiveIds = array_reduce(explode(" ", $interactiveIds), function ($result, $value) {
                $value = trim($value);

                if (!empty($value)) {
                    $result[] = $value;
                }

                return $result;
            }, []);

            if (!$interactiveIds) {
                $output->writeln("No IDs recognized. Exit.");
            }

            $ids = $interactiveIds;
        }

        return $ids;
    }

}
