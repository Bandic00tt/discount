<?php

namespace App\Repository;

use App\Entity\ProductExternal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ProductExternal|null find($id, $lockMode = null, $lockVersion = null)
 * @method ProductExternal|null findOneBy(array $criteria, array $orderBy = null)
 * @method ProductExternal[]    findAll()
 * @method ProductExternal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductExternalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductExternal::class);
    }

    // /**
    //  * @return ProductExternal[] Returns an array of ProductExternal objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ProductExternal
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
