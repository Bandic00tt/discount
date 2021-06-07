<?php

namespace App\Repository;

use App\Entity\DiscountLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscountLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountLog[]    findAll()
 * @method DiscountLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountLog::class);
    }

    // /**
    //  * @return DiscountLog[] Returns an array of DiscountLog objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DiscountLog
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
