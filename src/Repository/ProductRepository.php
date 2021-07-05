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
