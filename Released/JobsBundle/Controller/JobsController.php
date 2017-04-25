<?php


namespace Released\JobsBundle\Controller;


use Released\JobsBundle\Entity\Job;
use Released\JobsBundle\Entity\JobEvent;
use Released\JobsBundle\Entity\JobPackage;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/jobs")
 */
class JobsController extends Controller
{

    /**
     * @Route("/", name="jobs_index")
     * @Template()
     * @param Request $request
     * @return mixed
     */
    public function indexAction(Request $request)
    {
        $qb = $this->get('released.repository.job')->getListQueryBuilder();

        $type = $request->get('type');
        if ($type) {
            $qb->andWhere("t.slug = :slug")
                ->setParameter('slug', $type);
        }

        $pager = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pager->setCurrentPage(max(1, (integer) $request->query->get('page', 1)));
        $pager->setMaxPerPage(max(5, min(50, (integer) $request->query->get('per_page', 20))));

        $response = [
            'type_filter' => $type,
            'jobs' => $pager,
        ];

        return $this->buildResponse($response);
    }

    /**
     * Show packages
     * @Route("/{id}", name="jobs_show", requirements={"id"="\d+"})
     * @Template("ReleasedJobsBundle:Jobs:packages.html.twig")
     * @param $id
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showAction($id, Request $request)
    {
        $job = $this->getJobOr404($id);

        $packages = $this->get('released.repository.job_package')->getPackagesQueryBuilder($job);

        $pager = new Pagerfanta(new DoctrineORMAdapter($packages));
        $pager->setCurrentPage(max(1, (integer) $request->query->get('page', 1)));
        $pager->setMaxPerPage(max(5, min(50, (integer) $request->query->get('per_page', 50))));

        /** @var JobPackage[] $pager */
        return $this->buildResponse([
            'job'   => $job,
            'packages'  => $pager,
        ]);
    }

    /**
     * @Route("/{id}/events", name="jobs_show_events")
     * @Template()
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function eventsAction($id, Request $request)
    {
        $job = $this->getJobOr404($id);

        $jobEventRepository = $this->get('released.repository.job_event');

        $events = $jobEventRepository->getEventsQueryBuilder($job);
        $eventTypes = $jobEventRepository->getEventsTypes($job);

        $type = $request->get('type');
        if ($type) {
            $events->andWhere("e.type = :slug")
                ->setParameter('slug', $type);
        }
        
        $packageId = $request->get('package_id');
        if ($packageId) {
            $events->andWhere("e.jobPackage = :package_id")
                ->setParameter('package_id', $packageId);
        }
        

        $pager = new Pagerfanta(new DoctrineORMAdapter($events));
        $pager->setCurrentPage(max(1, (integer) $request->query->get('page', 1)));
        $pager->setMaxPerPage(max(5, min(50, (integer) $request->query->get('per_page', 50))));

        /** @var JobEvent[] $pager */
        return $this->buildResponse([
            'job'   => $job,
            'events'  => $pager,
            'event_types'   => $eventTypes,
            'type_filter' => $type,
            'package_id' => $packageId,
        ]);
    }

    /**
     * @param $id
     * @return Job
     */
    protected function getJobOr404($id)
    {
        $job = $this->get('released.repository.job')->getJobSummary($id);

        if (!$job) {
            throw $this->createNotFoundException("Job {$id} not found.");
        }

        return $job;
    }

    /**
     * @param $response
     * @return mixed
     */
    protected function buildResponse($response)
    {
        $types = $this->get('released.repository.job_type')->findAll();

        $response['types'] = $types;
        $response['processes'] = $this->getRunningJobs();
        $response['base_template'] = $this->container->getParameter('mobillogix_jobs.base_template');

        return $response;
    }

    private function getRunningJobs()
    {
        $c = $this->container;

        $execGrep = $c->getParameter('cmd_grep');
        $execPs = $c->getParameter('cmd_ps');
        $execAwk = $c->getParameter('cmd_awk');
        $env = $c->getParameter('kernel.environment');

        $processes = [];
        $rootDir = realpath($c->getParameter('kernel.root_dir') . "/../");
        $cmd = "{$execPs} aux | {$execGrep} {$env} | {$execGrep} {$rootDir} | {$execGrep} \:job | {$execGrep} -v \"/bin/sh\" |  {$execAwk} '{print $13}'";
        exec($cmd, $processes);

        return $processes;
    }

}