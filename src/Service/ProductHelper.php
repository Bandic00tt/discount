<?php
namespace App\Service;

use App\Entity\DiscountHistory;
use App\Entity\Product;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class ProductHelper
{
    private EntityManagerInterface $em;
    private DateHelper $dateHelper;

    public function __construct(EntityManagerInterface $em, DateHelper $dateHelper)
    {
        $this->em = $em;
        $this->dateHelper = $dateHelper;
    }

    /**
     * @param array $products
     * @return array
     * @throws Exception
     */
    public function getDiscountDates(array $products): array
    {
        /** @var DiscountHistory[] $history */
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
     * @return array
     */
    public function getDiscountHistory(array $products): array
    {
        $productIds = array_map(function (Product $product) {
            return $product->getProductId();
        }, $products);

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
}