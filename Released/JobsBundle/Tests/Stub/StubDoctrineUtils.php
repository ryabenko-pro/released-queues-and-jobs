<?php


namespace Released\JobsBundle\Tests\Stub;


use Doctrine\ORM\EntityManagerInterface;
use Released\Common\Doctrine\DoctrineUtils;

class StubDoctrineUtils extends DoctrineUtils
{

    /** @var EntityManagerInterface */
    protected $em;

    public function __construct(EntityManagerInterface $em = null)
    {
        $this->em = $em;
    }

    /** {@inheritdoc} */
    public function repeatable(\Closure $closure, $limit = 3)
    {

    }

    /** {@inheritdoc} */
    public function getEntityManager()
    {
        return $this->em;
    }

}