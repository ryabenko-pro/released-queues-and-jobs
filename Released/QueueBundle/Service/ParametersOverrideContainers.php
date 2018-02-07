<?php

namespace Released\QueueBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\FrozenParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class ParametersOverrideContainers extends Container
{

    /** @var Container */
    protected $delegate;

    public function __construct(ContainerInterface $delegate, ParameterBag $parameters)
    {
        $this->delegate = $delegate;
        parent::__construct(new FrozenParameterBag($parameters->all()));
    }

    public function set($id, $service)
    {
        $this->delegate->set($id, $service);
    }

    public function has($id)
    {
        return $this->delegate->has($id);
    }

    public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
    {
        return $this->delegate->get($id, $invalidBehavior);
    }

    public function initialized($id)
    {
        return $this->delegate->initialized($id);
    }

    public function getServiceIds()
    {
        return $this->delegate->getServiceIds();
    }

    public function compile()
    {
        $this->delegate->compile();
    }

    public function isFrozen()
    {
        return $this->delegate->isFrozen();
    }

    public function getParameterBag()
    {
        return parent::getParameterBag();
    }

    public function getParameter($name)
    {
        return parent::getParameter($name);
    }

    public function hasParameter($name)
    {
        return parent::hasParameter($name);
    }

    public function setParameter($name, $value)
    {
        parent::setParameter($name, $value);
    }

}