<?php

namespace App\Repository;

use App\Entity\DiscountHistory;
use App\Trait\QueryHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscountHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountHistory[]    findAll()
 * @method DiscountHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountHistoryRepository extends ServiceEntityRepository
{
    use QueryHelper;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ManagerRegistry $registry)
    {
        $this->em = $em;
        parent::__construct($registry, DiscountHistory::class);
    }

    /**
     * @param int $locationId
     * @param array $products
     * @return DiscountHistory[]
     */
    public function findAllByLocationIdAndProducts(int $locationId, array $products): array
    {
        $productIds = $this->getProductIds($products);

        return $this->em
            ->createQueryBuilder()
            ->select(['dh'])
            ->from(DiscountHistory::class, 'dh')
            ->andWhere('dh.location_id = :locationId')
            ->andWhere('dh.product_id in (:productIds)')
            ->setParameters([
                'locationId' => $locationId,
                'productIds' => $productIds,
            ])
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $productId
     * @param string $date
     * @return DiscountHistory
     * @throws NonUniqueResultException
     */
    public function findByProductIdAndTimeLimit(int $productId, string $date): DiscountHistory
    {
        $ts = strtotime($date);

        return $this->em
            ->createQueryBuilder()
            ->select(['dh'])
            ->from(DiscountHistory::class, 'dh')
            ->where('dh.product_id = :productId and dh.date_begin <= :ts and dh.date_end >= :ts')
            ->setParameters([
                'productId' => $productId,
                'ts' => $ts
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return DiscountHistory[] Returns an array of DiscountHistory objects
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
    public function findOneBySomeField($value): ?DiscountHistory
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
