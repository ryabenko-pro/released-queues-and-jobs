<?php

namespace Released\JobsBundle\Interfaces;


use Released\JobsBundle\Model\BaseProcess;

interface ProcessExecutorInterface
{

    /**
     * Executes process
     * @param BaseProcess $process
     * @return mixed
     */
    public function runProcess(BaseProcess $process);

    /**
     * Save error information
     *
     * @param BaseProcess $process
     * @param $currentPackage
     * @param $error
     * @return mixed
     */
    public function addError(BaseProcess $process, $currentPackage, $error);

    /**
     * Update current package number
     *
     * @param BaseProcess $process
     * @param $number
     * @return mixed
     */
    public function updatePackageNumber(BaseProcess $process, $number);

    /**
     * @param BaseProcess $process
     * @param string $message
     * @param $currentPackage
     * @return
     */
    public function addLog(BaseProcess $process, $message, $currentPackage = 0);

}