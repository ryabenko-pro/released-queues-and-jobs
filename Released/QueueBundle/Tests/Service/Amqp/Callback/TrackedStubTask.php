<?php

namespace Released\QueueBundle\Tests\Service\Amqp\Callback;

use Released\QueueBundle\Model\BaseTask;
use Released\QueueBundle\Service\TaskLoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TrackedStubTask extends BaseTask
{

    /** @var self[] */
    public static $instances = [];
    protected static $methodReturns = [];

    /** @var array[] */
    public $calls = [];

    /** {@inheritDoc} */
    public function validateData($data)
    {
        // We consider validate data always called on creation
        self::$instances[] = $this;

        parent::validateData($data);
    }

    /** {@inheritdoc} */
    public function execute(ContainerInterface $container, TaskLoggerInterface $logger)
    {
        $this->addCall('execute', func_get_args());

        return self::getMethodReturns('execute');
    }

    /** {@inheritdoc} */
    public function getType()
    {
        $this->addCall('getType', func_get_args());

        return self::getMethodReturns('getType');
    }

    protected function addCall($method, $args)
    {
        $this->calls[] = [
            'method' => $method,
            'args' => $args,
        ];
    }

    protected static function getMethodReturns($method, $default = null)
    {
        if (!isset(self::$methodReturns[$method])) {
            return $default;
        }

        return array_shift(self::$methodReturns[$method]);
    }

    public static function addMethodReturns($method, $return)
    {
        if (!isset(self::$methodReturns[$method])) {
            self::$methodReturns[$method] = [];
        }

        self::$methodReturns[$method][] = $return;
    }
}