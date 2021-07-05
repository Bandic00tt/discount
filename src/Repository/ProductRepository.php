<?php

namespace App\Repository;

use App\Entity\Product;
use App\Service\ProductList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em, ManagerRegistry $registry)
    {
        $this->em = $em;
        parent::__construct($registry, Product::class);
    }

    /**
     * @param int $locationId
     * @param int $page
     * @param string|null $searchQuery
     * @return Product[]
     */
    public function findListByParams(int $locationId, int $page, ?string $searchQuery): array
    {
        $offset = 0;
        if ($page > 1) {
            $offset = ($page - 1) * ProductList::PAGINATION_SIZE;
        }

        $query = $this->em
            ->createQueryBuilder()
            ->select(['p'])
            ->from(Product::class, 'p')
            ->andWhere('p.location_id = :locationId')
            ->setParameter('locationId', $locationId);

        if ($searchQuery) {
            $query->andWhere('p.name like :searchQuery')
                ->setParameter('searchQuery', '%'. $searchQuery .'%');
        }

        $query->orderBy('p.updated_at', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(ProductList::PAGINATION_SIZE);

        return $query->getQuery()->getResult();
    }

    /**
     * @param int $locationId
     * @param string|null $searchQuery
     * @return int
     * @throws NoResultException|NonUniqueResultException
     */
    public function findTotalByLocationIdAndSearchQuery(int $locationId, ?string $searchQuery): int
    {
        $query = $this->em
            ->createQueryBuilder()
            ->select(['count(p.id)'])
            ->from(Product::class, 'p')
            ->andWhere('p.location_id = :locationId')
            ->setParameter('locationId', $locationId);

        if ($searchQuery) {
            $query->andWhere('p.name like :searchQuery')
                ->setParameter('searchQuery', '%'. $searchQuery .'%');
        }

        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param int $locationId
     * @param array $results
     * @return int
     */
    public function updateByLocationId(int $locationId, array $results): int
    {
        $total = 0;
        $existingProductIds = $this->getExistingProductIds($locationId);
        $existingProductIdsForUpdate = [];
        foreach ($results as $result) {
            $productId = (int)$result['plu'];
            if (in_array($productId, $existingProductIds, false)) {
                $existingProductIdsForUpdate[] = $productId;
                continue;
            }

            $entity = new Product();
            $entity->setLocationId($locationId);
            $entity->setProductId($productId);
            $entity->setName($result['name']);
            $entity->setImgLink($result['img_link']);
            $entity->setIsImgLocal(0);
            $entity->setCreatedAt(time());
            $entity->setUpdatedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        $this->updateTimestampForExistingProducts($existingProductIdsForUpdate);

        return $total;
    }

    /**
     * Обновляем таймстамп у ранее сохраненных продуктов, для которых пришли новые данные по скидкам
     * @param array $productIds
     * @return void
     */
    private function updateTimestampForExistingProducts(array $productIds): void
    {
        $this->em
            ->createQueryBuilder()
            ->update(Product::class, 'p')
            ->set('p.updated_at', time())
            ->andWhere('p.product_id in (:productIds)')
            ->setParameter('productIds', $productIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @return int[]
     */
    private function getExistingProductIds(int $locationId): array
    {
        $res = $this->em->createQueryBuilder()
            ->select(['p.product_id'])
            ->from(Product::class, 'p')
            ->andWhere('p.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->getResult();

        return array_column($res, 'product_id');
    }

    /**
     * @param int $categoryId
     * @param array $results
     * @return int
     */
    public function getNumberOfCategorizedProducts(int $categoryId, array $results): int
    {
        $productIds = array_column($results, 'plu');

        return $this->em
            ->createQueryBuilder()
            ->update(Product::class, 'p')
            ->set('p.category_id', $categoryId)
            ->andWhere('p.product_id in (:productIds)')
            ->setParameters(['productIds' => $productIds])
            ->getQuery()
            ->execute();
    }

    // /**
    //  * @return Product[] Returns an array of Product objects
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
    public function findOneBySomeField($value): ?Product
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
