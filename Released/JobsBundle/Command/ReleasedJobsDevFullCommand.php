<?php

namespace Released\JobsBundle\Command;


use Released\Common\Command\BaseSingleCommand;
use Released\JobsBundle\Service\JobPlannerService;
use Released\JobsBundle\Service\ProcessExecutorService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasedJobsDevFullCommand extends BaseSingleCommand
{
    protected $symbols;

    /** @var JobPlannerService */
    private $planner;
    /** @var ProcessExecutorService */
    protected $executor;


    protected function configure()
    {
        $this->setName("released:jobs:dev-full")
            ->setDescription("Plan, execute, finish, repeat. For development purposes only!");
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeStart(InputInterface $input, OutputInterface $output)
    {
        $this->planner = $this->getContainer()->get('job_planner.service');
        $this->executor = $this->getContainer()->get('process_executor.service');

        $this->symbols = ['—', '\\', '|', '/'];
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        // Running as Symfony's Process component because entities do not get cleared properly in a single process.
        // TODO: running processes is slower
        $this->runAsProcess('released:jobs:plan');
        $this->runAsProcess('released:jobs:execute');
        $this->runAsProcess('released:jobs:finish');

        $output->write("\r" . $this->symbols[$this->cycles % count($this->symbols)]);
    }
}
