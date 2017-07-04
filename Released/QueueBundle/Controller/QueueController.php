<?php

namespace Released\QueueBundle\Controller;

use Released\QueueBundle\Entity\QueuedTask;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @Route("/queue")
 */
class QueueController extends Controller
{
    /**
     * @Template()
     * @param Request $request
     * @return array
     */
    public function indexAction(Request $request)
    {
        $types = $this->container->getParameter('released.queue.task_types');

        $qb = $this->get('released.queue.repository.queued_task')->createQueryBuilder("qt");

        $qb
             ->orderBy("qt.id", "DESC")
        ;

        $state = $request->get('state');
        if ($state) {
            if (is_string($state)) {
                $state = array_map("trim", explode(",", $state));
            }

            $qb->andWhere("qt.state IN (:state)")
                ->setParameter('state', $state);
        }

        $type = $request->get('type');
        if ($type) {
            $qb->andWhere("qt.type = :type")
                ->setParameter('type', $type);
        }

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pager->setCurrentPage(max(1, (integer) $request->query->get('page', 1)));
        $pager->setMaxPerPage(max(5, min(50, (integer) $request->query->get('per_page', 20))));

        $processes = $this->getRunningQueues();

        return [
            'base_template' => $this->container->getParameter('released.queue.base_template'),
            'types' => $types,
            'type_filter'  => $type,
            'tasks'  => $pager,
            'processes' => $processes,
        ];
    }

    /**
     * @param QueuedTask $task
     * @return Response
     */
    public function suspendAction(QueuedTask $task)
    {
        if ($task->isCancelled() || $task->isRunning()) {
            $this->addFlash('error', 'Task cannot be set to waiting.');

            return $this->redirect($this->generateUrl('released_queue_task_index'));
        }
        $task->dropState();
        $task->setState(QueuedTask::STATE_WAITING);
        $this->container->get('released.queue.repository.queued_task')->saveQueuedTask($task);
        $this->addFlash('warning', sprintf('Task #%s was set to waiting', $task->getId()));

        return $this->redirect($this->generateUrl('released_queue_task_index'));
    }

    /**
     * @param QueuedTask $task
     * @return Response
     */
    public function cancelAction(QueuedTask $task)
    {
        if ($task->isCancelled() || $task->isRunning()) {
            $this->addFlash('error', 'Task cannot be cancelled.');

            return $this->redirect($this->generateUrl('released_queue_task_index'));
        }
        $task->dropState();
        $task->setState(QueuedTask::STATE_CANCELLED);
        $this->container->get('released.queue.repository.queued_task')->saveQueuedTask($task);
        $this->addFlash('warning', sprintf('Task #%s was cancelled', $task->getId()));


        return $this->redirect($this->generateUrl('released_queue_task_index'));
    }

    /**
     * @param QueuedTask $task
     * @param Request $request
     * @return Response
     */
    public function retryAction(QueuedTask $task, Request $request)
    {
        $force = $request->get('force');
        if (($task->isCancelled() || $task->isRunning()) && !$force) {
            $forceUrl = $this->generateUrl('released_queue_task_retry', [
                'id' => $task->getId(),
                'force' => true,
            ], UrlGeneratorInterface::ABSOLUTE_URL);

            $message = sprintf('Task is running. Go to %s to force action', $forceUrl);
            $this->addFlash('error', $message);

            return $this->redirect($this->generateUrl('released_queue_task_index'));
        }
        $task->dropState();
        $this->container->get('released.queue.repository.queued_task')->saveQueuedTask($task);

        $this->addFlash('warning', sprintf('Task #%s was added to queue', $task->getId()));


        return $this->redirect($this->generateUrl('released_queue_task_index'));
    }

    /**
     * @Template()
     * @param QueuedTask $task
     * @return array
     */
    public function showAction(QueuedTask $task)
    {
        return [
            'base_template' => $this->container->getParameter('released.queue.base_template'),
            'task'  => $task,
        ];
    }

    private function getRunningQueues()
    {
        $c = $this->container;

        $execGrep = $c->getParameter('cmd_grep');
        $execPs = $c->getParameter('cmd_ps');
        $execAwk = $c->getParameter('cmd_awk');
        $env = $c->getParameter('kernel.environment');

        $processes = [];
        $rootDir = realpath($c->getParameter('kernel.root_dir') . "/../");
        $cmd = "{$execPs} aux | {$execGrep} {$env} | {$execGrep} {$rootDir} | {$execGrep} \\:queue | {$execGrep} -v \"/bin/sh\" |  {$execAwk} '{print $13}'";

        exec($cmd, $processes);

        return $processes;
    }

}
