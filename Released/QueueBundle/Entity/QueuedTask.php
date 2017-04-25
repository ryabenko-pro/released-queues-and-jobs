<?php


namespace Released\QueueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="queued_task")
 * @ORM\Entity(repositoryClass="Released\QueueBundle\Repository\QueuedTaskRepository")
 * @ORM\HasLifecycleCallbacks
 */
class QueuedTask
{
    const STATE_NEW = 'new';
    const STATE_RETRY = 'retry';
    const STATE_SELECTED = 'sel';
    const STATE_RUN = 'run';
    const STATE_DONE = 'done';
    const STATE_FAIL = 'fail';
    const STATE_DEPEND = 'dep';
    const STATE_CANCELLED = 'cancelled';
    const STATE_WAITING = 'wait';

    const PRIORITY_MEDIUM = 5;
    const PRIORITY_HIGH = 0;
    const PRIORITY_LOW = 10;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * TODO: make reference to QueuedTaskType
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * TODO: move to QueuedTaskType
     * @ORM\Column(type="string")
     */
    protected $priority = self::PRIORITY_MEDIUM;

    /**
     * @ORM\Column(type="string")
     */
    protected $state = self::STATE_NEW;

    /**
     * @ORM\Column(type="json_array", nullable=true)
     */
    protected $data;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $log;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    protected $startedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="selected_at", type="datetime", nullable=true)
     */
    protected $selectedAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="finished_at", type="datetime", nullable=true)
     */
    protected $finishedAt;

    /**
     * @var integer
     * @ORM\Column(name="parent_id", type="integer", nullable=true)
     */
    protected $parent;

    /**
     * @var integer
     * @ORM\Column(name="pid", type="integer", nullable=true)
     */
    protected $pid;
    
    /**
     * @var \DateTime
     * @ORM\Column(name="scheduled_at", type="datetime", nullable = true)
     */
    protected $scheduledAt;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  mixed $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param  int  $priority
     * @return self
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param  string $state
     * @return self
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param  mixed $data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * @param  mixed $log
     * @return self
     */
    public function setLog($log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param  \DateTime $createdAt
     * @return self
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * @param  \DateTime $startedAt
     * @return self
     */
    public function setStartedAt($startedAt)
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSelectedAt()
    {
        return $this->selectedAt;
    }

    /**
     * @param  \DateTime $selectedAt
     * @return self
     */
    public function setSelectedAt($selectedAt)
    {
        $this->selectedAt = $selectedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFinishedAt()
    {
        return $this->finishedAt;
    }

    /**
     * @param  \DateTime $finishedAt
     * @return self
     */
    public function setFinishedAt($finishedAt)
    {
        $this->finishedAt = $finishedAt;

        return $this;
    }

    /**
     * @param $message
     * @param string $type
     * @return self
     */
    public function addLog($message, $type = 'info')
    {
        $this->log .= sprintf("[%s]: %s\n---\n", $type, $message);

        return $this;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
        $this->setStateDepend();
    }

    public function setStateDepend()
    {
        $this->setState(self::STATE_DEPEND);
    }

    /**
     * @return bool
     */
    public function isDone()
    {
        return self::STATE_DONE == $this->state;
    }

    /**
     * @return int
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * @param  int $pid
     * @return self
     */
    public function setPid($pid)
    {
        $this->pid = $pid;

        return $this;
    }

    public function isCancelled()
    {
        return $this->getState() === self::STATE_CANCELLED;
    }

    public function isWaiting()
    {
        return $this->getState() === self::STATE_WAITING;
    }

    public function isRunning()
    {
        return $this->getState() === self::STATE_RUN;
    }

    public function isFinished()
    {
        return in_array($this->getState(), array(
            self::STATE_FAIL,
            self::STATE_DONE
        ));
    }

    public function dropState()
    {
        $this->setState(self::STATE_NEW);
        $this->setStartedAt(null);
        $this->setFinishedAt(null);
    }

    /**
     * @return \DateTime
     */
    public function getScheduledAt()
    {
        return $this->scheduledAt;
    }

    /**
     * @param \DateTime $scheduledAt
     * @return $this
     */
    public function setScheduledAt($scheduledAt)
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }
}
