<?php

namespace Released\Common\Factory;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

/**
 * TODO: move to CompilerPass
 * This class is used because Entity Manager may differ for Queues and Jobs
 */
class RepositoryFactory
{

    /** @var EntityManager */
    protected $em;

    function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * Create repository for entity
     * @param $entity
     * @return EntityRepository
     */
    public function getRepository($entity)
    {
        return $this->em->getRepository($entity);
    }

}