<?php


namespace Released\QueueBundle\Repository;


use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Released\QueueBundle\Entity\QueuedTask;
use PDO;

class QueuedTaskRepository extends EntityRepository
{

    // Select tasks one by one
    const RUN_TASKS_LIMIT = 1;

    /**
     * @param QueuedTask $entity
     * @return int
     */
    public function saveQueuedTask(QueuedTask $entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush($entity);

        return $entity->getId();
    }

    /**
     * Mark task as started
     * @param QueuedTask $task
     * @param int $pid
     */
    public function setTaskStarted(QueuedTask $task, $pid)
    {
        $this->createQueryBuilder("qt")
            ->update()
            ->set('qt.state', ':started')
            ->set('qt.startedAt', ':started_at')
            ->set('qt.pid', ':pid')
            ->where('qt.id = :id')
            ->setParameters([
                'started' => $task::STATE_RUN,
                'started_at'    => new \DateTime(),
                'pid'    => $pid,
                'id'    => $task->getId(),
            ])->getQuery()->execute();
    }

    /**
     * @param string[]|null $types If set - select only tasks on this types
     * @param string[]|null $noTypes If set - exclude tasks from select
     * @param null $serverId
     * @param int $limit
     * @return QueuedTask[]
     * @throws ORMException
     */
    public function getQueuedTasksForRun($types = null, $noTypes = null, $serverId = null, $limit = self::RUN_TASKS_LIMIT)
    {
        $em = $this->getEntityManager();

        $qb = $this->createQueryBuilder('qt');

        $qb->where('qt.state IN (:states)')
            ->setParameter('states', [QueuedTask::STATE_NEW, QueuedTask::STATE_RETRY])
            ->andWhere('qt.scheduledAt IS NULL OR qt.scheduledAt < :current_date')
            ->setParameter('current_date', new \DateTime())
            ->setMaxResults($limit)
            ->orderBy('qt.createdAt');

        if (!is_null($types) && !empty($types)) {
            $qb->andWhere('qt.type IN (:types)')
                ->setParameter('types', (array)$types);
        }

        if (!is_null($noTypes) && !empty($noTypes)) {
            $qb->andWhere('qt.type NOT IN (:no_types)')
                ->setParameter('no_types', (array)$noTypes);
        }

        if (!is_null($serverId)) {
            $qb->andWhere('qt.server IS NULL OR qt.server = :server')
                ->setParameter('server', $serverId);
        }

        $q = $qb->getQuery();


        $em->beginTransaction();
        $result = $q->setLockMode(LockMode::PESSIMISTIC_WRITE)->execute();

        $ids = array_map(function(QueuedTask $task) {
            return $task->getId();
        }, $result);

        $this->createQueryBuilder('qt')
            ->update()
            ->set('qt.state', ':selected')
            ->set('qt.selectedAt', ':selected_at')
            ->where('qt.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->setParameter('selected', QueuedTask::STATE_SELECTED)
            ->setParameter('selected_at', new \DateTime())
            ->getQuery()->execute();

        $em->commit();

        return $result;
    }

    /**
     * Only runs tasks, which were selected, but not started.
     * For tasks, which was started decision should be made manually
     * because it can order something and die, and we should not order it again
     *
     * @param int $limit Tasks limit to GC
     * @param int $timeout Timeout after which task should be GCed
     * @return int Number of collected tasks
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function runGc($limit = self::RUN_TASKS_LIMIT, $timeout = 600)
    {
        $em = $this->getEntityManager();

        $qb = $this->createQueryBuilder('qt');

        $q = $qb->where('(qt.state = :selected AND (qt.selectedAt < :gc_time OR qt.selectedAt IS NULL))')
            ->setParameters([
                'selected' => QueuedTask::STATE_SELECTED,
                'gc_time'   => new \DateTime("- {$timeout} seconds"),
            ])
            ->setMaxResults($limit)
            ->orderBy('qt.createdAt')
            ->getQuery();


        $em->beginTransaction();
        $result = $q->setLockMode(LockMode::PESSIMISTIC_WRITE)->execute();

        $ids = array_map(function(QueuedTask $task) {
            return $task->getId();
        }, $result);

        $this->createQueryBuilder('qt')
            ->update()
            ->set('qt.state', ':new')
            ->where('qt.id IN (:ids)')
            ->setParameters([
                'ids' => $ids,
                'new' => QueuedTask::STATE_RETRY
            ])
            ->getQuery()->execute();

        $em->commit();

        return count($result);
    }

    /**
     * @param array $ids
     * @return QueuedTask[]
     */
    public function findByIds($ids)
    {
        $qb = $this->createQueryBuilder('qt');

        $qb->where('qt.id IN (:ids)')
            ->setParameter('ids', $ids);

        return $qb->getQuery()->execute();
    }

    /**
     * @param QueuedTask $entity
     * @throws \RuntimeException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateDependTasks(QueuedTask $entity)
    {
        $em = $this->getEntityManager();

        $em->beginTransaction();

        $statement = $em->getConnection()
            ->prepare("SELECT qt.id FROM queued_task qt WHERE qt.state = :state AND qt.parent_id = :parent FOR UPDATE");
        $statement->bindValue('state', QueuedTask::STATE_DEPEND);
        $statement->bindValue('parent', $entity->getId());
        $statement->execute();

        $ids = $statement->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($ids)) {
            $this->createQueryBuilder('qt')
                ->update()
                ->set('qt.state', ':new')
                ->where('qt.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->setParameter('new', QueuedTask::STATE_NEW)
                ->setParameter('ids', $ids)
                ->getQuery()->execute();
        }

        $em->commit();
    }
}