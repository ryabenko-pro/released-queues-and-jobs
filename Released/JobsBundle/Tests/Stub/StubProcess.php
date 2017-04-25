<?php

namespace Released\JobsBundle\Tests\Stub;


use Released\JobsBundle\Model\BaseProcess;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StubProcess extends BaseProcess
{

    protected function doExecute($package, ContainerInterface $container)
    {

    }
}