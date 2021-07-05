<?php

namespace App\Repository;

use App\Entity\City;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method City|null find($id, $lockMode = null, $lockVersion = null)
 * @method City|null findOneBy(array $criteria, array $orderBy = null)
 * @method City[]    findAll()
 * @method City[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CityRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ManagerRegistry $registry)
    {
        $this->em = $em;
        parent::__construct($registry, City::class);
    }

    public function clearByRegionId(int $regionId)
    {
        $this->em->createQueryBuilder()
            ->delete(City::class, 'c')
            ->where('c.region_id = :regionId')
            ->setParameter('regionId', $regionId)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $cities
     * @return int
     */
    public function update(array $cities): int
    {
        $total = 0;
        foreach ($cities as $city) {
            $entity = new City();
            $entity->setRegionId($city['region']);
            $entity->setCityId($city['id']);
            $entity->setName($city['name']);
            $entity->setCreatedAt(time());
            $entity->setUpdatedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }

    // /**
    //  * @return City[] Returns an array of City objects
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
    public function findOneBySomeField($value): ?City
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
