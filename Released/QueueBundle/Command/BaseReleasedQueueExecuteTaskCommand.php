<?php

namespace Released\QueueBundle\Command;

use Released\Common\Command\BaseSingleCommand;
use Released\QueueBundle\Service\ParametersOverrideContainers;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

abstract class BaseReleasedQueueExecuteTaskCommand extends BaseSingleCommand
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this
            ->setDescription("Execute queued tasks. May be started in parallel several instances.")
            ->addOption("with", "w", InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, "You can pass some string parameters to container in format 'name:value', just `name` is same like `name:true`. You can not override existing parameters!")
            ->addOption("type", "t", InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, "If present only execute tasks of this types.")
            ->addOption("no-type", "s", InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, "If present exclude tasks of this types.");
    }

    /** {@inheritdoc} */
    protected function beforeStart(InputInterface $input, OutputInterface $output)
    {
        $service = $this->getContainer()->get('released.queue.task_queue.service_database');

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

        $service->setContainer(new ParametersOverrideContainers($container, $parameters));
    }


}

