<?php
namespace App\Service;

use App\Dto\ProductListViewParams;
use App\Dto\ProductPaginationViewParams;
use Exception;

class ProductList
{
    private const PAGINATION_SIZE = 20;

    public function __construct(
        private DiscountHelper $discountHelper,
        private array $products,
        private array $discountHistory
    ) {}

    /**
     * @throws Exception
     */
    public function getProductListViewParams(): ProductListViewParams
    {
        $year = date('Y');

        $dto = new ProductListViewParams();
        $dto->year = $year;
        $dto->yearDates = $this->discountHelper->dateHelper->getYearDates($year);
        $dto->discountDates = $this->discountHelper->getDiscountDates($year, $this->discountHistory);
        $dto->discountYears = $this->discountHelper->getDiscountYears($this->discountHistory);
        $dto->activeProductDiscounts = $this->discountHelper->getActiveProductDiscounts($this->products);

        return $dto;
    }

    /**
     * @param int $page
     * @param int $totalProducts
     * @return ProductPaginationViewParams
     */
    public function getProductPaginationViewParams(int $page, int $totalProducts): ProductPaginationViewParams
    {
        $totalPages = ceil($totalProducts / DiscountHelper::MAX_RESULTS);
        $firstPage = 1;
        $lastPage = min($totalPages, self::PAGINATION_SIZE);

        if ($totalPages > $lastPage && $page > self::PAGINATION_SIZE / 2) {
            if (($page + self::PAGINATION_SIZE / 2) < $totalPages) {
                $lastPage = $page + self::PAGINATION_SIZE / 2;
            } else {
                $lastPage = $totalPages;
            }

            $firstPage = $lastPage - self::PAGINATION_SIZE;
        }

        $dto = new ProductPaginationViewParams();
        $dto->firstPage = $firstPage;
        $dto->lastPage = $lastPage;
        $dto->totalPages = $totalPages;

        return $dto;
    }
}