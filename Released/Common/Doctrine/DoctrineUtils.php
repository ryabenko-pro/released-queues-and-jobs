<?php

namespace Released\Common\Doctrine;


use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class DoctrineUtils
{
    /** @var Registry */
    protected $doctrine;
    /** @var string */
    protected $name;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(Registry $doctrine, $name = null, LoggerInterface $logger = null)
    {
        $this->doctrine = $doctrine;
        $this->name = $this->cleanEmName($name);
        $this->logger = $logger;
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
                if (!is_null($this->logger)) {
                    $this->logger->log(Logger::NOTICE, "Deadlock detected. Restarting.");
                }

                if (--$repeat == 0) {
                    if (!is_null($this->logger)) {
                        $this->logger->log(Logger::ERROR, "Too many deadlocks detected. Throwing exception.");
                    }

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

    /**
     * @param $name
     * @return mixed
     */
    protected function cleanEmName($name)
    {
        if (preg_match("/doctrine.orm.(\w+)_entity_manager/", $name, $matches)) {
            return $matches[1];
        }

        return $name;
    }


}