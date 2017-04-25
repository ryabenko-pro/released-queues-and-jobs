<?php

namespace Released\JobsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Released\JobsBundle\Util\TimestampableEntity;


/**
 * @ORM\Table(name="job_type")
 * @ORM\Entity(repositoryClass="Released\JobsBundle\Repository\JobTypeRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class JobType
{

    use TimestampableEntity;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @ORM\Column(type="string")
     */
    protected $slug;

    /**
     * @ORM\Column(type="float")
     */
    protected $priority;

    /**
     * @ORM\Column(name="planning_interval", type="integer", nullable=true)
     */
    protected $planningInterval;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $slug
     * @return self
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param float $priority
     * @return self
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return float
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @return mixed
     */
    public function getPlanningInterval()
    {
        return $this->planningInterval;
    }

    /**
     * @param mixed $planningInterval
     * @return self
     */
    public function setPlanningInterval($planningInterval)
    {
        $this->planningInterval = $planningInterval;
        return $this;
    }

}
