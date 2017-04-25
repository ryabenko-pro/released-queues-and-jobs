<?php

namespace Released\JobsBundle\Command;

use Released\JobsBundle\Service\Persistence\JobProcessPersistenceService;
use Released\JobsBundle\Service\ProcessExecutorService;
use Released\Common\Command\BaseSingleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasedJobsExecuteCommand extends BaseSingleCommand
{
    /** @var  JobProcessPersistenceService */
    protected $service;

    protected function configure()
    {
        $this->setName("mobillogix:jobs:execute")
            ->setDescription("Run packages. Can be run in parallel.");
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeStart(InputInterface $input, OutputInterface $output)
    {
        $this->service = $this->getContainer()->get('job_process_persistence.service');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $processes = $this->service->getProcessesForRun();

        foreach ($processes as $process) {
            $this->runAsProcess('mobillogix:jobs:package-run', [
                $process->getEntity()->getId(),
            ]);
        }
    }

}
