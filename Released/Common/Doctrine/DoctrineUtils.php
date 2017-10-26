<?php

namespace Released\Common\Doctrine;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManager;

class DoctrineUtils
{
    /** @var Registry */
    protected $doctrine;
    /** @var string */
    protected $name;

    public function __construct(Registry $doctrine, $name = null)
    {
        $this->doctrine = $doctrine;
        $this->name = $name;
    }

    /**
     * @param \Closure $closure
     * @param int $limit
     * @throws DeadlockException
     */
    public function repeatable(\Closure $closure, $limit = 3)
    {

        $repeat = $limit;

        while (true) {
            try {
                $em = $this->getEntityManager();
                $em->transactional($closure);

                break;
            } catch (DeadlockException $exception) {
                echo "Deadlock caught\n";

                if (--$repeat == 0) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager($this->name);

        if (!$em->isOpen()) {
            $em = $this->doctrine->resetManager($this->name);
        }

        return $em;
    }


}