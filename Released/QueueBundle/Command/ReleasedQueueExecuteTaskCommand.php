<?php

namespace Released\QueueBundle\Command;

use Released\Common\Command\BaseSingleCommand;
use Released\QueueBundle\Service\TaskQueueService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ReleasedQueueExecuteTaskCommand extends BaseSingleCommand
{
    /** @var TaskQueueService */
    protected $service;

    protected function configure()
    {
        $this->setName("released:queue:execute-task")
            ->setDescription("Execute queued tasks. May be started in parallel several instances.")
            ->addOption("with", "w", InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, "You can pass some string parameters to container in format 'name:value', just `name` is same like `name:true`. You can not override existing parameters!")
            ->addOption("type", "t", InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, "If present only execute tasks of this types.")
            ->addArgument('ids', InputArgument::OPTIONAL|InputArgument::IS_ARRAY, 'Run tasks with specific id, regardless ist state.');
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeStart(InputInterface $input, OutputInterface $output)
    {
        $this->service = $this->getContainer()->get('released.queue.task_queue.service_database');

        /** @var Container $container */
        $container = $this->getContainer();
        $parameters = new ParameterBag($container->getParameterBag()->all());

        foreach ($input->getOption('with') as $with) {
            $parts = array_pad(explode(":", $with), 2, true);

            $name = array_shift($parts);
            $value = count($parts) == 1 ? $parts[0] : implode(':', $parts);

            if ($parameters->has($name)) {
                throw new \Exception("Parameter '{$name}' already defined. You can not override existing parameters!");
            }

            $parameters->set($name, $value);
        }

        $this->service->setContainer(new ParametersOverrideContainers($container, $parameters));
    }

    /**
     * {@inheritdoc}
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $ids = $input->getArgument('ids');

        if (empty($ids)) {
            $types = $input->getOption('type');
            $this->service->runTasks($types);
        } else {
            if ($input->getOption('permanent')) {
                throw new \Exception("Ids can not be used with 'permanent' option");
            }

            $entities = $this->getContainer()->get('released.queue.repository.queued_task')->findByIds($ids);
            foreach ($entities as $entity) {
                $task = $this->service->mapEntityToTask($entity);
                $this->service->executeTask($task);
            }
        }
    }

}

class ParametersOverrideContainers extends Container
{

    /** @var Container */
    protected $delegate;

    public function __construct(ContainerInterface $delegate, ParameterBag $parameters)
    {
        $this->delegate = $delegate;
        parent::__construct(new FrozenParameterBag($parameters->all()));
    }

    public function set($id, $service)
    {
        $this->delegate->set($id, $service);
    }

    public function has($id)
    {
        return $this->delegate->has($id);
    }

    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        return $this->delegate->get($id, $invalidBehavior);
    }

    public function initialized($id)
    {
        return $this->delegate->initialized($id);
    }

    public function getServiceIds()
    {
        return $this->delegate->getServiceIds();
    }

    public function compile()
    {
        $this->delegate->compile();
    }

    public function isFrozen()
    {
        return $this->delegate->isFrozen();
    }

    public function getParameterBag()
    {
        return parent::getParameterBag();
    }

    public function getParameter($name)
    {
        return parent::getParameter($name);
    }

    public function hasParameter($name)
    {
        return parent::hasParameter($name);
    }

    public function setParameter($name, $value)
    {
        parent::setParameter($name, $value);
    }

}