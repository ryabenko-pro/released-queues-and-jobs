<?php

namespace Released\JobsBundle\Entity;

use DateTime;
use Released\JobsBundle\Util\TimestampableEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="job")
 * @ORM\Entity(repositoryClass="Released\JobsBundle\Repository\JobRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Job
{
    use TimestampableEntity;

    const STATUS_NEW = "new";
    const STATUS_RUN = "run";
    const STATUS_DONE = "done";
    const STATUS_CANCEL = "cancel";

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Released\JobsBundle\Entity\JobType")
     * @ORM\JoinColumn(name="job_type_id")
     */
    protected $jobType;

    /**
     * @ORM\OneToMany(targetEntity="Released\JobsBundle\Entity\JobPackage", mappedBy="job")
     */
    protected $packages;

    /**
     * @var JobEvent[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="Released\JobsBundle\Entity\JobEvent", mappedBy="job")
     */
    protected $events;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $data;

    /**
     * @ORM\Column(type="string")
     */
    protected $status = self::STATUS_NEW;

    /**
     * @ORM\Column(name="is_need_planning", type="boolean")
     */
    protected $isNeedPlanning = true;

    /**
     * @ORM\Column(name="packages_total", type="integer")
     */
    protected $packagesTotal = 0;

    /**
     * @ORM\Column(name="packages_finished", type="integer")
     */
    protected $packagesFinished = 0;

    /**
     * Optional. If task have some exceptional meaning or started manually
     * @ORM\Column(type="text", nullable=true)
     */
    protected $comment;

    /**
     * @ORM\Column(name="planned_at", type="datetime", nullable=true)
     */
    protected $plannedAt;

    /**
     * @ORM\Column(name="next_planning_at", type="datetime", nullable=true)
     */
    protected $nextPlanningAt;

    /**
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    protected $finishedAt;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->packages = new ArrayCollection();
        $this->events = new ArrayCollection();
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|array $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param boolean $isNeedPlanning
     * @return self
     */
    public function setIsNeedPlanning($isNeedPlanning)
    {
        $this->isNeedPlanning = $isNeedPlanning;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsNeedPlanning()
    {
        return $this->isNeedPlanning;
    }

    /**
     * @param integer $packagesTotal
     * @return self
     */
    public function setPackagesTotal($packagesTotal)
    {
        $this->packagesTotal = $packagesTotal;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPackagesTotal()
    {
        return $this->packagesTotal;
    }

    /**
     * @param int $count
     * @return self
     */
    public function incPackagesTotal($count)
    {
        $this->packagesTotal += $count;

        return $this;
    }

    /**
     * @param integer $packagesFinished
     * @return self
     */
    public function setPackagesFinished($packagesFinished)
    {
        $this->packagesFinished = $packagesFinished;

        return $this;
    }

    /**
     * @return integer
     */
    public function getPackagesFinished()
    {
        return $this->packagesFinished;
    }

    /**
     * @param string $comment
     * @return self
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param DateTime $plannedAt
     * @return self
     */
    public function setPlannedAt($plannedAt)
    {
        $this->plannedAt = $plannedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getPlannedAt()
    {
        return $this->plannedAt;
    }

    /**
     * @return mixed
     */
    public function getNextPlanningAt()
    {
        return $this->nextPlanningAt;
    }

    /**
     * @param mixed $nextPlanningAt
     * @return self
     */
    public function setNextPlanningAt($nextPlanningAt)
    {
        $this->nextPlanningAt = $nextPlanningAt;
        return $this;
    }

    /**
     * @param DateTime $finishedAt
     * @return self
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param JobType $jobType
     * @return self
     */
    public function setJobType(JobType $jobType = null)
    {
        $this->jobType = $jobType;

        return $this;
    }

    /**
     * @return JobType
     */
    public function getJobType()
    {
        return $this->jobType;
    }

    /**
     * @param JobPackage $package
     * @return Job
     */
    public function addPackage(JobPackage $package)
    {
        $this->packages[] = $package;

        return $this;
    }

    /**
     * @param JobPackage $package
     */
    public function removePackage(JobPackage $package)
    {
        $this->packages->removeElement($package);
    }

    /**
     * @return Collection
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param JobEvent $event
     * @return Job
     */
    public function addEvent(JobEvent $event)
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * @param JobEvent $event
     */
    public function removeEvent(JobEvent $event)
    {
        $this->events->removeElement($event);
    }

    /**
     * @param null $type
     * @return JobEvent[]
     */
    public function getEvents($type = null)
    {
        if (is_null($type)) {
            return $this->events;
        }

        $result = [];
        /** @var JobEvent $event */
        foreach ($this->events as $event) {
            if ($event->getType() == $type) {
                $result[] = $event;
            }
        }

        return $result;
    }

}
