<?php

namespace App\Repository;

use App\Entity\DiscountLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscountLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountLog[]    findAll()
 * @method DiscountLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountLogRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ManagerRegistry $registry)
    {
        $this->em = $em;
        parent::__construct($registry, DiscountLog::class);
    }

    /**
     * @param int $locationId
     * @param array $results
     */
    public function logByLocationId(int $locationId, array $results)
    {
        $entity = new DiscountLog();
        $entity->setLocationId($locationId);
        $entity->setData($results);
        $entity->setSize(count($results));
        $entity->setSavedAt(time());

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
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
