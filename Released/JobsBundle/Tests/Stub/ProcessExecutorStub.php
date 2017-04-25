<?php

namespace Released\JobsBundle\Tests\Stub;


use Released\JobsBundle\Interfaces\ProcessExecutorInterface;
use Released\JobsBundle\Model\BaseProcess;

class ProcessExecutorStub implements ProcessExecutorInterface
{

    public function runProcess(BaseProcess $process)
    {
    }

    public function addError(BaseProcess $process, $currentPackage, $error)
    {
    }

    public function updatePackageNumber(BaseProcess $process, $number)
    {
    }

    public function addLog(BaseProcess $process, $message, $currentPackage = 0)
    {
    }
}