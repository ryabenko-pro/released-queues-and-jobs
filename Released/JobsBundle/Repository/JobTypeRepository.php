<?php

namespace Released\JobsBundle\Repository;

use Released\JobsBundle\Entity\JobType;
use Doctrine\ORM\EntityRepository;

/**
 * @method JobType findAll
 */
class JobTypeRepository extends EntityRepository
{

    public function saveJobType(JobType $type)
    {
        $em = $this->getEntityManager();
        $em->persist($type);
        $em->flush($type);
    }

}
