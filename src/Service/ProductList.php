<?php
namespace App\Service;

use App\Dto\ProductListViewParams;
use App\Dto\ProductPaginationViewParams;
use App\Repository\DiscountRepository;
use Exception;
use JetBrains\PhpStorm\Pure;

class ProductList
{
    public const PAGINATION_SIZE = 20;

    public function __construct(
        private DateHelper $dateHelper,
        private array $products,
        private array $discountHistory
    ) {}

    /**
     * @throws Exception
     */
    public function getProductListViewParams(DiscountRepository $discountRepository): ProductListViewParams
    {
        $year = date('Y');

        $dto = new ProductListViewParams();
        $dto->year = $year;
        $dto->yearDates = $this->dateHelper->getYearDates($year);
        $dto->discountDates = $this->dateHelper->getDiscountDates($year, $this->discountHistory);
        $dto->discountYears = $this->dateHelper->getDiscountYears($this->discountHistory);
        $dto->activeProductDiscounts = $discountRepository->findActiveProductDiscounts($this->products);

        return $dto;
    }

    /**
     * @param int $page
     * @param int $totalProducts
     * @return ProductPaginationViewParams
     */
    #[Pure]
    public function getProductPaginationViewParams(int $page, int $totalProducts): ProductPaginationViewParams
    {
        $totalPages = ceil($totalProducts / self::PAGINATION_SIZE);
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