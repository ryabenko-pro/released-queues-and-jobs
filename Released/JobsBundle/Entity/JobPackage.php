<?php

namespace Released\JobsBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Released\JobsBundle\Util\TimestampableEntity;


/**
 * @ORM\Table(name="job_package")
 * @ORM\Entity(repositoryClass="Released\JobsBundle\Repository\JobPackageRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class JobPackage
{

    use TimestampableEntity;

    const STATUS_NEW = "new";
    const STATUS_SELECTED = "selected";
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
     * @ORM\ManyToOne(targetEntity="Released\JobsBundle\Entity\Job", inversedBy="packages")
     */
    protected $job;

    /**
     * @ORM\Column(type="string")
     */
    protected $status = self::STATUS_NEW;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $options;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $packages;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $errors = [];

    /**
     * @ORM\Column(name="current_package", type="integer")
     */
    protected $currentPackage = 0;

    /**
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    protected $startedAt;

    /**
     * Selected to run. Field for GC purposes.
     * If startedAt is more then X seconds ago, but lastPackageStartedAt is null - must be GCed.
     * @ORM\Column(name="selected_at", type="datetime", nullable=true)
     */
    protected $selectedAt;

    /**
     * @ORM\Column(name="last_package_started_at", type="datetime", nullable=true)
     */
    protected $lastPackageStartedAt;

    /**
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    protected $finishedAt;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
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
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $options
     * @return self
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * @param array $packages
     * @return self
     */
    public function setPackages($packages)
    {
        $this->packages = $packages;

        return $this;
    }

    /**
     * @return array
     */
    public function getPackages()
    {
        return $this->packages;
    }

    /**
     * @param array $errors
     * @return self
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param integer $currentPackage
     * @return self
     */
    public function setCurrentPackage($currentPackage)
    {
        $this->currentPackage = $currentPackage;

        return $this;
    }

    /**
     * @return integer
     */
    public function getCurrentPackage()
    {
        return $this->currentPackage;
    }

    /**
     * @param DateTime $startedAt
     * @return self
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @return mixed
     */
    public function getSelectedAt()
    {
        return $this->selectedAt;
    }

    /**
     * @param mixed $selectedAt
     * @return self
     */
    public function setSelectedAt($selectedAt)
    {
        $this->selectedAt = $selectedAt;
        return $this;
    }

    /**
     * @param DateTime $lastPackageStartedAt
     * @return self
     */
    public function setLastPackageStartedAt($lastPackageStartedAt)
    {
        $this->lastPackageStartedAt = $lastPackageStartedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLastPackageStartedAt()
    {
        return $this->lastPackageStartedAt;
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
}
