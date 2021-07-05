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
     * @param int $locationId
     * @param int $productId
     * @param string $date
     * @return DiscountHistory
     * @throws NonUniqueResultException
     */
    public function findByLimit(int $locationId, int $productId, string $date): DiscountHistory
    {
        $ts = strtotime($date);

        return $this->em
            ->createQueryBuilder()
            ->select(['dh'])
            ->from(DiscountHistory::class, 'dh')
            ->andWhere('dh.location_id = :locationId')
            ->andWhere('dh.product_id = :productId')
            ->andWhere('dh.date_begin <= :ts and dh.date_end >= :ts')
            ->setParameters([
                'locationId' => $locationId,
                'productId' => $productId,
                'ts' => $ts
            ])
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param int $locationId
     * @param array $results
     * @return int
     */
    public function updateByLocationId(int $locationId, array $results): int
    {
        $total = 0;
        $existingDiscountIds = $this->getExistingDiscountIds($locationId);
        foreach ($results as $result) {
            $discountId = (int)$result['promo']['id'];
            if (in_array($discountId, $existingDiscountIds, false)) {
                continue;
            }

            $entity = new DiscountHistory();
            $entity->setLocationId($locationId);
            $entity->setProductId($result['plu']);
            $entity->setDiscountId($discountId);
            $entity->setDateBegin(strtotime($result['promo']['date_begin']));
            $entity->setDateEnd(strtotime($result['promo']['date_end']));
            $entity->setPriceDiscount($result['current_prices']['price_promo__min']);
            $entity->setPriceNormal($result['current_prices']['price_reg__min']);
            $entity->setSavedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }

    /**
     * @return int[]
     */
    public function getExistingDiscountIds(int $locationId): array
    {
        $res = $this->em->createQueryBuilder()
            ->select(['dh.discount_id'])
            ->from(DiscountHistory::class, 'dh')
            ->andWhere('dh.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->getResult();

        return array_column($res, 'discount_id');
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
