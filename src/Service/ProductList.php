<?php
namespace App\Service;

use App\Dto\ProductListViewParams;
use App\Dto\ProductPaginationViewParams;
use App\Entity\Product;
use App\Repository\DiscountHistoryRepository;
use App\Repository\DiscountRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Exception;
use JetBrains\PhpStorm\Pure;

class ProductList
{
    public const PAGINATION_SIZE = 20;
    private DateHelper $dateHelper;
    private array $products;
    private array $discountHistory;
    private int $totalProducts;

    #[Pure]
    public function __construct(
        private int $cityId,
        private int $page,
        private ?string $searchQuery
    ) {
        $this->dateHelper = new DateHelper();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function init(ProductRepository $productRepository, DiscountHistoryRepository $discountHistoryRepository)
    {
        $this->products = $productRepository->findListByParams($this->cityId, $this->page, $this->searchQuery);
        $this->discountHistory = $discountHistoryRepository->findAllByLocationIdAndProducts($this->cityId, $this->products);
        $this->totalProducts = $productRepository->findTotalByLocationIdAndSearchQuery($this->cityId, $this->searchQuery);
    }

    /**
     * @return Product[]
     */
    public function getProducts(): array
    {
        return $this->products;
    }

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
     * @return ProductPaginationViewParams
     */
    #[Pure]
    public function getProductPaginationViewParams(): ProductPaginationViewParams
    {
        $totalPages = ceil($this->totalProducts / self::PAGINATION_SIZE);
        $firstPage = 1;
        $lastPage = min($totalPages, self::PAGINATION_SIZE);

        if ($totalPages > $lastPage && $this->page > self::PAGINATION_SIZE / 2) {
            if (($this->page + self::PAGINATION_SIZE / 2) < $totalPages) {
                $lastPage = $this->page + self::PAGINATION_SIZE / 2;
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