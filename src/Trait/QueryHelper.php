<?php
namespace App\Trait;

use App\Entity\Product;

/**
 * Class DiscountHelper
 * @package App\Service
 */
trait QueryHelper
{
    /**
     * @param Product[] $products
     * @return array
     */
    public function getProductIds(array $products): array
    {
        return array_map(function (Product $product) {
            return $product->getProductId();
        }, $products);
    }
}