<?php
namespace App\Service;

use App\Entity\Discount;
use App\Entity\DiscountHistory;
use App\Entity\Product;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Exception;

/**
 * todo: transfer most of the queries in this class to DiscountRepository
 * Class DiscountHelper
 * @package App\Service
 */
class DiscountHelper
{
    private EntityManagerInterface $em;
    public DateHelper $dateHelper;

    public function __construct(EntityManagerInterface $em, DateHelper $dateHelper)
    {
        $this->em = $em;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @return array
     */
    public function getFavoritedProducts(): array
    {
        return $this->em
            ->createQueryBuilder()
            ->select(['p'])
            ->from(Product::class, 'p')
            ->where('p.is_favorited = ?1')
            ->setParameter('1', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $products
     * @return array
     * @throws Exception
     */
    public function getDiscountDates(array $products): array
    {
        $history = $this->getDiscountHistory($products);
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
                    $productDates[] = $date;
                }
            }

            $result[$productId] = $productDates;
        }

        return $result;
    }

    /**
     * @param array $products
     * @return DiscountHistory[]
     */
    public function getDiscountHistory(array $products): array
    {
        $productIds = $this->getProductIds($products);

        return $this->em
            ->createQueryBuilder()
            ->select(['dh'])
            ->from(DiscountHistory::class, 'dh')
            ->where('dh.product_id in (:productIds)')
            ->setParameter('productIds', $productIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Product[] $products
     * @return Discount[]
     * @throws QueryException
     */
    public function getProductDiscounts(array $products): array
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