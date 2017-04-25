<?php

namespace Released\JobsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Released\JobsBundle\Util\TimestampableEntity;


/**
 * @ORM\Table(name="job_event")
 * @ORM\Entity(repositoryClass="Released\JobsBundle\Repository\JobEventRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class JobEvent
{

    use TimestampableEntity;

    const TYPE_RUN = "run";
    const TYPE_PLAN = "plan";
    const TYPE_DONE = "done";
    const TYPE_REPLAN = "replan";
    const TYPE_ERROR = "error";
    const TYPE_LOG = "log";

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Released\JobsBundle\Entity\Job", inversedBy="events")
     */
    protected $job;

    /**
     * @ORM\ManyToOne(targetEntity="Released\JobsBundle\Entity\JobPackage")
     * @ORM\JoinColumn(name="job_package_id", nullable=true)
     */
    protected $jobPackage;

    /**
     * Event can point on particular package item
     * @ORM\Column(name="job_package_number", type="integer", nullable=true)
     */
    protected $jobPackageNumber;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $message
     * @return self
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Job $job
     * @return self
     */
    public function setJob(Job $job = null)
    {
        $this->job = $job;

        return $this;
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param JobPackage $jobProcess
     * @return self
     */
    public function setJobPackage(JobPackage $jobProcess = null)
    {
        $this->jobPackage = $jobProcess;

        return $this;
    }

    /**
     * @return JobPackage
     */
    public function getJobPackage()
    {
        return $this->jobPackage;
    }

    /**
     * @return mixed
     */
    public function getJobPackageNumber()
    {
        return $this->jobPackageNumber;
    }

    /**
     * @param mixed $jobPackageNumber
     * @return self
     */
    public function setJobPackageNumber($jobPackageNumber)
    {
        $this->jobPackageNumber = $jobPackageNumber;

        return $this;
    }

}
