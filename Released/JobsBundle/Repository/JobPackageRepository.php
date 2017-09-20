<?php

namespace Released\JobsBundle\Repository;

use Doctrine\DBAL\Connection;
use Released\JobsBundle\Entity\Job;
use Released\JobsBundle\Entity\JobPackage;
use Doctrine\ORM\EntityRepository;
use PDO;


class JobPackageRepository extends EntityRepository
{

    /**
     * @param JobPackage $package
     * @return int
     */
    public function savePackage(JobPackage $package)
    {
        $em = $this->getEntityManager();

        $connection = $em->getConnection();
        $old = $connection->getTransactionIsolation();

        $connection->setTransactionIsolation(Connection::TRANSACTION_READ_COMMITTED);
        $em->beginTransaction();

        $em->persist($package);
        $em->flush($package);

        $em->commit();
        $connection->setTransactionIsolation($old);


        return $package->getId();
    }

    /**
     * @param int $limit
     * @return JobPackage[]
     */
    public function getPackagesForRun($limit)
    {
        $em = $this->getEntityManager();
        $driver = $em->getConnection()->getDriver()->getName();

        $connection = $em->getConnection();
        $old = $connection->getTransactionIsolation();
        $em->beginTransaction();

        // Postgres requires implicit table name
        $forUpdateOf = 'pdo_pgsql' == $driver ? 'OF p' : '';

        // Select ids with FOR UPDATE
        $q = "SELECT p.id FROM job_package p LEFT JOIN job j ON p.job_id = j.id
              LEFT JOIN job_type jt ON j.job_type_id = jt.id WHERE p.status = :status
              ORDER BY jt.priority DESC LIMIT :limit FOR UPDATE {$forUpdateOf}";

        $st = $em->getConnection()->prepare($q);
        $st->bindValue('status', JobPackage::STATUS_NEW);
        $st->bindValue('limit', $limit, PDO::PARAM_INT);

        $st->execute();
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);

        // Select packages
        $packages = $this->createQueryBuilder("p")
            ->select("p", "job", "job_type")
            ->leftJoin("p.job", "job")
            ->leftJoin("job.jobType", "job_type")
            ->where("p.id IN (:ids)")
            ->setParameter('ids', $ids)->getQuery()->execute();

        // Update packages
        $qb = $this->createQueryBuilder("p")
            ->update()
            ->set("p.status", ':status')
            ->set("p.selectedAt", ':now')
            ->where("p.id IN (:ids)")
            ->setParameters([
                'status' => JobPackage::STATUS_SELECTED,
                'now'    => new \DateTime(),
                'ids'    => $ids
            ]);

        $qb->getQuery()->execute();

        $em->commit();
        $connection->setTransactionIsolation($old);

        return $packages;
    }

    public function updatePackageNumber(JobPackage $package, $number)
    {
        $qb = $this->createQueryBuilder("p")
            ->update()
            ->set("p.currentPackage", $number)
            ->where("p.id = :id")
            ->setParameter("id", $package->getId());

        $qb->getQuery()->execute();
    }

    /**
     * @param int $id
     * @return JobPackage
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getPackage($id)
    {
        $qb = $this->createQueryBuilder("p")
            ->select("p", "job", "job_type")
            ->leftJoin("p.job", "job")
            ->leftJoin("job.jobType", "job_type")
            ->where("p.id = :id")
            ->setParameter('id', $id);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Job $job
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getPackagesQueryBuilder($job)
    {
        $qb = $this->createQueryBuilder("p")
            ->select("p", "job", "job_type")
            ->leftJoin("p.job", "job")
            ->leftJoin("job.jobType", "job_type")
            ->where("p.job = :job")
            ->setParameter('job', $job);

        return $qb;
    }

}
