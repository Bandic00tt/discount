<?php
namespace App\Service;

use App\Dto\ProductItemByYearParams;
use App\Dto\ProductItemViewParams;
use App\Entity\Product;
use App\Repository\DiscountHistoryRepository;
use App\Repository\DiscountRepository;
use App\Repository\ProductRepository;
use Exception;
use JetBrains\PhpStorm\Pure;

class ProductItem
{
    private DateHelper $dateHelper;
    private ?Product $product;
    private array $discountHistory;

    #[Pure]
    public function __construct(
        private int $cityId,
        private int $productId,
    ) {
        $this->dateHelper = new DateHelper();
    }

    public function init(ProductRepository $productRepository, DiscountHistoryRepository $discountHistoryRepository)
    {
        $this->product = $productRepository->findOneBy(['product_id' => $this->productId]);
        $this->discountHistory = $discountHistoryRepository->findAllByLocationIdAndProducts($this->cityId, [$this->product]);
    }

    /**
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        return $this->product;
    }

    /**
     * @throws Exception
     */
    public function getProductItemViewParams(DiscountRepository $discountRepository): ProductItemViewParams
    {
        $discountYears = $this->dateHelper->getDiscountYears($this->discountHistory)[$this->product->getProductId()] ?? [];
        $datesByYears = [];
        $discountDatesByYears = [];
        foreach ($discountYears as $year) {
            $discountDatesByYears[$year] = $this->dateHelper->getDiscountDates($year, $this->discountHistory)[$this->product->getProductId()];
            $datesByYears[$year] = $this->dateHelper->getYearDates($year);
        }

        $dto = new ProductItemViewParams();
        $dto->activeDiscounts = $discountRepository->findActiveProductDiscounts([$this->product]);
        $dto->discountYears = $discountYears;
        $dto->datesByYears = $datesByYears;
        $dto->discountDatesByYears = $discountDatesByYears;

        return $dto;
    }

    /**
     * @throws Exception
     */
    public function getProductItemByYearParams(int $year): ProductItemByYearParams
    {
        $dto = new ProductItemByYearParams();
        $dto->yearDates = $this->dateHelper->getYearDates($year);
        $dto->discountDates = $this->dateHelper->getDiscountDates($year, $this->discountHistory)[$this->product->getId()];
        $dto->discountYears = $this->dateHelper->getDiscountYears($this->discountHistory)[$this->product->getId()];

        return $dto;
    }
}