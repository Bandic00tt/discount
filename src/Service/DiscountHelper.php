<?php
namespace App\Service;

use App\Entity\Discount;
use App\Entity\DiscountHistory;
use App\Entity\Product;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Exception;

/**
 * todo: transfer most of the queries in this class to DiscountRepository
 * Class DiscountHelper
 * @package App\Service
 */
class DiscountHelper
{
    public const MAX_RESULTS = 20;

    private EntityManagerInterface $em;
    public DateHelper $dateHelper;

    public function __construct(EntityManagerInterface $em, DateHelper $dateHelper)
    {
        $this->em = $em;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @param int $locationId
     * @param int $page
     * @return Query
     */
    public function getProducts(int $locationId, int $page): Query
    {
        $offset = ($page - 1) * self::MAX_RESULTS;

        return $this->em
            ->createQueryBuilder()
            ->select(['p'])
            ->from(Product::class, 'p')
            ->andWhere('p.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->orderBy('p.updated_at', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(self::MAX_RESULTS)
            ->getQuery();
    }

    /**
     * @param int $locationId
     * @return int
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getTotalProducts(int $locationId): int
    {
        return $this->em
            ->createQueryBuilder()
            ->select(['count(p.id)'])
            ->from(Product::class, 'p')
            ->andWhere('p.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param DiscountHistory[] $history
     * @return array
     */
    public function getDiscountYears(array $history): array
    {
        $result = [];
        foreach ($history as $item) {
            $yearBegin = date('Y', $item->getDateBegin());
            $yearEnd = date('Y', $item->getDateEnd());

            $result[$item->getProductId()][] = $yearBegin;
            $result[$item->getProductId()][] = $yearEnd;
        }

        // Проходимся еще раз по годам со скидками, убираем дубликаты и сортируем
        foreach ($result as $productId => $years) {
            $years = array_unique($years);
            sort($years);
            $result[$productId] = $years;
        }

        return $result;
    }

    /**
     * @param string $year
     * @param DiscountHistory[] $history
     * @return array
     * @throws Exception
     */
    public function getDiscountDates(string $year, array $history): array
    {
        $result = [];
        foreach ($history as $item) {
            $result[$item->getProductId()][] = $this->dateHelper->getDatesFromRange(
                new DateTime(date('Y-m-d', $item->getDateBegin())),
                new DateTime(date('Y-m-d', $item->getDateEnd()))
            );
        }

        foreach ($result as $productId => $dateRanges) {
            $productDates = [];
            foreach ($dateRanges as $dateRange) {
                foreach ($dateRange as $date) {
                    $cond = date('Y', strtotime($date)) === $year
                        || date('Y', strtotime($date)) === (string)((int) $year - 1)
                        || date('Y', strtotime($date)) === (string)((int) $year + 1);

                    if ($cond) {
                        $productDates[] = $date;
                    }
                }
            }

            $result[$productId] = $productDates;
        }

        return $result;
    }

    /**
     * @param int $locationId
     * @param array $products
     * @return DiscountHistory[]
     */
    public function getDiscountHistory(int $locationId, array $products): array
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
     * @param Product[] $products
     * @return Discount[]
     * @throws QueryException
     */
    public function getActiveProductDiscounts(array $products): array
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
     * @param Product[] $products
     * @return array
     */
    private function getProductIds(array $products): array
    {
        return array_map(function (Product $product) {
            return $product->getProductId();
        }, $products);
    }

    /**
     * @param int $productId
     * @param string $date
     * @return DiscountHistory
     * @throws NonUniqueResultException
     */
    public function getTimeLimitedDiscountData(int $productId, string $date): DiscountHistory
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
}