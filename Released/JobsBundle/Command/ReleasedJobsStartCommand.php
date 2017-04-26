<?php

namespace Released\JobsBundle\Command;


use Released\JobsBundle\Service\JobPlannerService;
use Released\Common\Command\BaseSingleCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReleasedJobsStartCommand extends BaseSingleCommand
{
    protected $needToCheckStopFile = false;

    protected function configure()
    {
        $this->setName("released:jobs:start")
            ->setDescription("Start jobs");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     * @throws \Exception
     */
    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $stopFileName = $this->getStopFileName();

        if (file_exists($stopFileName)) {
            if (false === unlink($stopFileName)) {
                throw new \Exception("Can't delete stop file '{$stopFileName}'");
            }
        } else {
            $output->write("<info>Stop file '{$stopFileName}' doesn't exist. Jobs already work</info>");
        }
    }
}