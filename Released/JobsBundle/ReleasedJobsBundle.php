<?php

namespace Released\JobsBundle;

use Released\JobsBundle\Command as Cmd;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ReleasedJobsBundle extends Bundle
{
    public function registerCommands(Application $application)
    {
        $application->add(new Cmd\ReleasedJobsCreateCommand());
        $application->add(new Cmd\ReleasedJobsDevFullCommand());
        $application->add(new Cmd\ReleasedJobsExecuteCommand());
        $application->add(new Cmd\ReleasedJobsFinishCommand());
        $application->add(new Cmd\ReleasedJobsPackageRunCommand());
        $application->add(new Cmd\ReleasedJobsPlanCommand());
        $application->add(new Cmd\ReleasedJobsStartCommand());
        $application->add(new Cmd\ReleasedJobsStopCommand());
    }

}
