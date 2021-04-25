<?php

namespace App\Repository;

use App\Entity\CourseEvent;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method CourseEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method CourseEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method CourseEvent[]    findAll()
 * @method CourseEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CourseEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseEvent::class);
    }

    /**
    * @return CourseEvent[] Returns an array of CourseEvent objects
    */
    public function findAfterToday(User $user)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->setParameter('val', $user)
            ->andWhere('c.startsAt >= :date')
            ->setParameter('date', new \DateTime())
            ->orderBy('c.startsAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return CourseEvent[] Returns an array of CourseEvent objects
     */
    public function findOlderThanAMonth(User $user)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :val')
            ->setParameter('val', $user)
            ->andWhere('c.startsAt <= :date')
            ->setParameter('date', new \DateTime('-30 days'))
            ->orderBy('c.startsAt', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }



    // /**
    //  * @return CourseEvent[] Returns an array of CourseEvent objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?CourseEvent
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
