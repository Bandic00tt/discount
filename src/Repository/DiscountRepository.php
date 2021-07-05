<?php

namespace App\Repository;

use App\Entity\Discount;
use App\Trait\QueryHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Discount|null find($id, $lockMode = null, $lockVersion = null)
 * @method Discount|null findOneBy(array $criteria, array $orderBy = null)
 * @method Discount[]    findAll()
 * @method Discount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountRepository extends ServiceEntityRepository
{
    use QueryHelper;

    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ManagerRegistry $registry)
    {
        $this->em = $em;
        parent::__construct($registry, Discount::class);
    }

    public function findActiveProductDiscounts(array $products): array
    {
        $productIds = $this->getProductIds($products);

        return $this->em
            ->createQueryBuilder()
            ->select(['d'])
            ->from(Discount::class, 'd')
            ->where('d.product_id in (:productIds)')
            ->setParameter('productIds', $productIds)
            ->indexBy('d', 'd.product_id')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int $locationId
     * @param array $results
     * @return int
     */
    public function updateByLocationId(int $locationId, array $results): int
    {
        $total = 0;
        foreach ($results as $result) {
            $entity = new Discount();
            $entity->setLocationId($locationId);
            $entity->setProductId($result['plu']);
            $entity->setDiscountId($result['promo']['id']);
            $entity->setName($result['name']);
            $entity->setImgLink($result['img_link']);
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
     * Очищаем данные по скидкам для перезаписи, обновления данных
     */
    public function clearByLocationId(int $locationId)
    {
        $this->em->createQueryBuilder()
            ->delete(Discount::class, 'd')
            ->andWhere('d.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->execute();
    }

    // /**
    //  * @return Discount[] Returns an array of Discount objects
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
    public function findOneBySomeField($value): ?Discount
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
