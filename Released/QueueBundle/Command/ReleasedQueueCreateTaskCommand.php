<?php

namespace Released\QueueBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasedQueueCreateTaskCommand extends ContainerAwareCommand
{

    const BASE_TASK = 'Released\QueueBundle\Model\BaseTask';

    protected function configure()
    {
        $this->setName("released:queue:create-task");
        $this
            ->addArgument("type", InputArgument::REQUIRED, "Task type")
            ->addArgument("data", InputArgument::OPTIONAL, "Task data in json string (use quotes). Empty array by default.", '{}');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $typeName = $input->getArgument('type');
        $data = (array)@json_decode($input->getArgument('data'));

        $types = $this->getContainer()->getParameter('released.queue.task_types');

        if (!isset($types[$typeName])) {
            throw new \Exception("Type '{$typeName}' not found.");
        }

        $type = $types[$typeName];

        $class = $type['class_name'];
        if (!is_subclass_of($class, self::BASE_TASK)) {
            throw new \Exception("Task class '{$class}' must be subclass of " . self::BASE_TASK);
        }

        $task = new $class($data);

        $service = $this->getContainer()->get('released.queue.task_queue.service');
        $id = $service->enqueue($task);

        $output->writeln(sprintf(
            "Task #%d with type '%s' added using transport %s.",
            $id,
            $typeName,
            $this->getContainer()->getParameter('released.queue.transport')
        ));
    }

}