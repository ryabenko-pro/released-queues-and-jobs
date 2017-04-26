<?php

namespace Released\JobsBundle\Command;


use Released\JobsBundle\Service\JobPlannerService;
use Released\Common\Command\BaseSingleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasedJobsPlanCommand extends BaseSingleCommand
{
    /** @var JobPlannerService */
    protected $service;

    protected function configure()
    {
        $this->setName("released:jobs:plan")
            ->setDescription("Run jobs planning to create packages");
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeStart(InputInterface $input, OutputInterface $output)
    {
        $this->service = $this->getContainer()->get('job_planner.service');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->service->runPlanning();
    }

}