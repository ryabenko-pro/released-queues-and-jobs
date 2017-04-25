<?php

namespace Released\JobsBundle\Tests\Stub;


use Released\JobsBundle\Model\BaseJob;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StubJob extends BaseJob
{

    protected $type;

    /**
     * @param mixed $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param $processes
     * @return self
     */
    public function setProcesses($processes)
    {
        $this->processes = $processes;

        return $this;
    }

    /**
     * @return string JobType slug
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Creating processes for incoming data
     * @param ContainerInterface $container
     * @return bool True if need more planing, false if no more planning needed
     */
    protected function doPlan(ContainerInterface $container)
    {

    }

}