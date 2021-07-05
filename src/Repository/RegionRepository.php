<?php

namespace App\Repository;

use App\Entity\Region;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Region|null find($id, $lockMode = null, $lockVersion = null)
 * @method Region|null findOneBy(array $criteria, array $orderBy = null)
 * @method Region[]    findAll()
 * @method Region[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegionRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ManagerRegistry $registry)
    {
        $this->em = $em;
        parent::__construct($registry, Region::class);
    }

    public function clear()
    {
        $this->em->createQueryBuilder()
            ->delete(Region::class, 'r')
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $regions
     * @return int
     */
    public function update(array $regions): int
    {
        $total = 0;
        foreach ($regions as $region) {
            $entity = new Region();
            $entity->setRegionId($region['id']);
            $entity->setName($region['name']);
            $entity->setSavedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }

    // /**
    //  * @return Region[] Returns an array of Region objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Region
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
