<?php /** @noinspection DuplicatedCode */

namespace App\Service\Shop\Five;

use App\Entity\Discount;
use App\Entity\DiscountHistory;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

class DataHandler
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $results
     * @return int
     */
    public function updateDiscounts(array $results): int
    {
        $total = 0;
        foreach ($results as $result) {
            $entity = new Discount();
            $entity->setLocationId(ApiClient::DEFAULT_LOCATION_ID);
            $entity->setProductId($result['id']);
            $entity->setName($result['name']);
            $entity->setImgLink($result['img_link']);
            $entity->setDateBegin(strtotime($result['promo']['date_begin']));
            $entity->setDateEnd(strtotime($result['promo']['date_end']));
            $entity->setPriceDiscount($result['current_prices']['price_promo__min']);
            $entity->setPriceNormal($result['current_prices']['price_reg__min']);
            $entity->setSavedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }

    /**
     * Очищаем данные по скидкам для перезаписи, обновления данных
     */
    public function clearDiscounts()
    {
        $this->em->createQueryBuilder()
            ->delete(Discount::class, 'd')
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $results
     * @return int
     */
    public function updateProducts(array $results): int
    {
        $total = 0;
        $existingProductIds = $this->getExistingProductIds();
        foreach ($results as $result) {
            if (in_array((int)$result['id'], $existingProductIds, true)) {
                continue;
            }

            $entity = new Product();
            $entity->setProductId($result['id']);
            $entity->setName($result['name']);
            $entity->setImgLink($result['img_link']);
            $entity->setCreatedAt(time());
            $entity->setUpdatedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }

    /**
     * @return array
     */
    private function getExistingProductIds(): array
    {
        $res = $this->em->createQueryBuilder()
            ->select(['p.product_id'])
            ->from(Product::class, 'p')
            ->getQuery()
            ->getResult();

        return array_map(function ($item) {
            return (int)$item['product_id'];
        }, $res);
    }

    /**
     * @param array $results
     * @return int
     */
    public function updateHistory(array $results): int
    {
        $total = 0;
        foreach ($results as $result) {
            $entity = new DiscountHistory();
            $entity->setLocationId(ApiClient::DEFAULT_LOCATION_ID);
            $entity->setProductId($result['id']);
            $entity->setDateBegin(strtotime($result['promo']['date_begin']));
            $entity->setDateEnd(strtotime($result['promo']['date_end']));
            $entity->setPriceDiscount($result['current_prices']['price_promo__min']);
            $entity->setPriceNormal($result['current_prices']['price_reg__min']);
            $entity->setSavedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }
}