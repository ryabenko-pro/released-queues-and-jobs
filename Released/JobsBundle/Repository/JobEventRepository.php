<?php

namespace Released\JobsBundle\Repository;

use Released\JobsBundle\Entity\Job;
use Released\JobsBundle\Entity\JobEvent;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;


class JobEventRepository extends EntityRepository
{

    /**
     * @param JobEvent $event
     * @return int
     */
    public function saveJobEvent(JobEvent $event)
    {
        $em = $this->getEntityManager();
        $em->persist($event);
        $em->flush($event);

        return $event->getId();
    }

    /**
     * @param Job $job
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getEventsQueryBuilder($job)
    {
        $qb = $this->createQueryBuilder("e")
            ->select("e", "p")
            ->leftJoin("e.jobPackage", "p")
            ->where("e.job = :job")
            ->orderBy("e.createdAt", "DESC")
            ->orderBy("e.id", "DESC")
            ->setParameter('job', $job);

        return $qb;
    }

    public function getEventsTypes($job)
    {
        $qb = $this->createQueryBuilder("e")
            ->select("e.type", "count(e)")
            ->where("e.job = :job")
            ->groupBy("e.type")
            ->setParameter('job', $job);

        $result = [];
        /** @var JobEvent $event */
        foreach ($qb->getQuery()->setHydrationMode(Query::HYDRATE_ARRAY)->execute() as $event) {
            $result[] = [
                'type' => $event['type'],
                'count' => $event[1],
            ];
        }

        return $result;
    }

}
