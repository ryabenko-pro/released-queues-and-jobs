<?php

namespace Released\JobsBundle\Model;


use Released\JobsBundle\Entity\JobPackage;
use Released\JobsBundle\Interfaces\ProcessExecutorInterface;
use Released\JobsBundle\Util\Options;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseProcess
{

    /** @var array */
    private $packages;
    /** @var Options */
    private $options;

    /** @var JobPackage */
    protected $entity;

    /**
     * @param $packages
     * @param Options $options
     */
    final public function __construct($packages, Options $options = null)
    {
        $this->packages = $packages;
        $this->options = is_null($options) ? new Options() : $options;
    }

    /**
     * @return JobPackage
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param JobPackage $entity
     * @return self
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * TODO: move this method to another class
     * @param ProcessExecutorInterface $executor
     * @param ContainerInterface $container
     * @throws \Exception
     */
    public function execute(ProcessExecutorInterface $executor, ContainerInterface $container)
    {
        $counter = 0;
        $this->beforeExecute($container);

        foreach ((array)$this->packages as $package) {
            $executor->updatePackageNumber($this, ++$counter);

            try {
                ob_start();
                $this->doExecute($package, $container);

                $this->logOutput($executor, $counter);
            } catch (\PHPUnit_Framework_ExpectationFailedException $exception) {
                $this->logOutput($executor, $counter);

                // For unit tests only
                throw $exception;
            } catch (\Exception $exception) {
                $this->logOutput($executor, $counter);

                $executor->addError($this,
                    $counter,
                    sprintf("Exception [%s] while executing task with message: '%s'",
                        get_class($exception),
                        $exception->getMessage()
                    )
                );
            }
        }

        $this->afterExecute($container);
    }

    /**
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    protected function beforeExecute(ContainerInterface $container)
    {

    }

    protected function afterExecute(ContainerInterface $container)
    {

    }

    abstract protected function doExecute($package, ContainerInterface $container);

    /**
     * @param ProcessExecutorInterface $executor
     * @param $counter
     */
    protected function logOutput(ProcessExecutorInterface $executor, $counter)
    {
        $output = ob_get_contents();
        $output = trim($output);
        ob_end_clean();

        if (!empty($output)) {
            $executor->addLog($this, $output, $counter);
        }
    }

}