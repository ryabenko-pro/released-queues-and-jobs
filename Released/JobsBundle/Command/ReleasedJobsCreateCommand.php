<?php

namespace Released\JobsBundle\Command;


use Released\JobsBundle\Service\JobPlannerService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasedJobsCreateCommand extends ContainerAwareCommand
{

    /** @var JobPlannerService */
    protected $service;

    protected function configure()
    {
        $this->setName("mobillogix:jobs:create");
        $this->addArgument("type", InputArgument::REQUIRED, "Job type")
            ->addArgument("data", InputArgument::OPTIONAL, "Job data in json string (use quotes). Empty array by default.", '{}');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $data = (array)@json_decode($input->getArgument('data'));

        $service = $this->getContainer()->get('job_planner.service');
        $job = $service->createJobInstance($type, $data);

        $id = $service->addJob($job);

        $output->writeln("Job #{$id} with type '{$type}' added.");
    }

}