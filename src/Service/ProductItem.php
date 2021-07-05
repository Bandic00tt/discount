<?php
namespace App\Service;


use App\Dto\ProductItemByYearParams;
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