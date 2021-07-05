<?php
namespace App\Service;


use App\Dto\ProductItemViewParams;
use App\Entity\Product;
use App\Repository\DiscountRepository;
use Exception;

class ProductItem
{
    public function __construct(
        private DateHelper $dateHelper,
        private Product $product,
        private array $discountHistory
    ) {}

    /**
     * @throws Exception
     */
    public function getProductItemViewParams(DiscountRepository $discountRepository): ProductItemViewParams
    {
        $activeDiscounts = $discountRepository->findActiveProductDiscounts([$this->product]);
        $discountYears = $this->dateHelper->getDiscountYears($this->discountHistory)[$this->product->getProductId()] ?? [];
        $datesByYears = [];
        $discountDatesByYears = [];
        foreach ($discountYears as $year) {
            $discountDatesByYears[$year] = $this->dateHelper->getDiscountDates($year, $this->discountHistory)[$this->product->getProductId()];
            $datesByYears[$year] = $this->dateHelper->getYearDates($year);
        }

        $dto = new ProductItemViewParams();
        $dto->activeDiscounts = $activeDiscounts;
        $dto->discountYears = $discountYears;
        $dto->datesByYears = $datesByYears;
        $dto->discountDatesByYears = $discountDatesByYears;

        return $dto;
    }
}