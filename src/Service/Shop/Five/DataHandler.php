<?php /** @noinspection DuplicatedCode */

namespace App\Service\Shop\Five;

use App\Entity\Category;
use App\Entity\City;
use App\Entity\Discount;
use App\Entity\DiscountHistory;
use App\Entity\DiscountLog;
use App\Entity\Product;
use App\Entity\Region;
use Doctrine\ORM\EntityManagerInterface;

class DataHandler
{
    public const MOSCOW_ID = 8145;

    public EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param int $locationId
     * @param array $results
     * @return int
     */
    public function updateDiscounts(int $locationId, array $results): int
    {
        $total = 0;
        foreach ($results as $result) {
            $entity = new Discount();
            $entity->setLocationId($locationId);
            $entity->setProductId($result['plu']);
            $entity->setDiscountId($result['promo']['id']);
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
    public function clearDiscounts(int $locationId)
    {
        $this->em->createQueryBuilder()
            ->delete(Discount::class, 'd')
            ->andWhere('d.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $locationId
     * @param array $results
     * @return int
     */
    public function updateProducts(int $locationId, array $results): int
    {
        $total = 0;
        $existingProductIds = $this->getExistingProductIds($locationId);
        $existingProductIdsForUpdate = [];
        foreach ($results as $result) {
            $productId = (int)$result['plu'];
            if (in_array($productId, $existingProductIds, false)) {
                $existingProductIdsForUpdate[] = $productId;
                continue;
            }

            $entity = new Product();
            $entity->setLocationId($locationId);
            $entity->setProductId($productId);
            $entity->setName($result['name']);
            $entity->setImgLink($result['img_link']);
            $entity->setIsImgLocal(0);
            $entity->setCreatedAt(time());
            $entity->setUpdatedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        $this->updateTimestampForExistingProducts($existingProductIdsForUpdate);

        return $total;
    }

    /**
     * Обновляем таймстамп у ранее сохраненных продуктов, для которых пришли новые данные по скидкам
     * @param array $productIds
     * @return void
     */
    private function updateTimestampForExistingProducts(array $productIds): void
    {
        $this->em
            ->createQueryBuilder()
            ->update(Product::class, 'p')
            ->set('p.updated_at', time())
            ->andWhere('p.product_id in (:productIds)')
            ->setParameter('productIds', $productIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @return int[]
     */
    private function getExistingProductIds(int $locationId): array
    {
        $res = $this->em->createQueryBuilder()
            ->select(['p.product_id'])
            ->from(Product::class, 'p')
            ->andWhere('p.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->getResult();

        return array_column($res, 'product_id');
    }

    /**
     * @param int $locationId
     * @param array $results
     * @return int
     */
    public function updateHistory(int $locationId, array $results): int
    {
        $total = 0;
        $existingDiscountIds = $this->getExistingDiscountIds($locationId);
        foreach ($results as $result) {
            $discountId = (int)$result['promo']['id'];
            if (in_array($discountId, $existingDiscountIds, false)) {
                continue;
            }

            $entity = new DiscountHistory();
            $entity->setLocationId($locationId);
            $entity->setProductId($result['plu']);
            $entity->setDiscountId($discountId);
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
     * @return int[]
     */
    public function getExistingDiscountIds(int $locationId): array
    {
        $res = $this->em->createQueryBuilder()
            ->select(['dh.discount_id'])
            ->from(DiscountHistory::class, 'dh')
            ->andWhere('dh.location_id = :locationId')
            ->setParameter('locationId', $locationId)
            ->getQuery()
            ->getResult();

        return array_column($res, 'discount_id');
    }

    /**
     * @param int $locationId
     * @param array $results
     */
    public function logDiscounts(int $locationId, array $results)
    {
        $entity = new DiscountLog();
        $entity->setLocationId($locationId);
        $entity->setData($results);
        $entity->setSize(count($results));
        $entity->setSavedAt(time());

        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
    }

    public function clearRegions()
    {
        $this->em->createQueryBuilder()
            ->delete(Region::class, 'r')
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $regions
     * @return int
     */
    public function updateRegions(array $regions): int
    {
        $total = 0;
        foreach ($regions as $region) {
            $entity = new Region();
            $entity->setRegionId($region['id']);
            $entity->setName($region['name']);
            $entity->setSavedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }

    public function clearRegionCities(int $regionId)
    {
        $this->em->createQueryBuilder()
            ->delete(City::class, 'c')
            ->where('c.region_id = :regionId')
            ->setParameter('regionId', $regionId)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $cities
     * @return int
     */
    public function updateRegionCities(array $cities): int
    {
        $total = 0;
        foreach ($cities as $city) {
            $entity = new City();
            $entity->setRegionId($city['region']);
            $entity->setCityId($city['id']);
            $entity->setName($city['name']);
            $entity->setCreatedAt(time());
            $entity->setUpdatedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }

    public function clearParentCategories()
    {
        $this->em->createQueryBuilder()
            ->delete(Category::class, 'c')
            ->where('c.category_id is NULL')
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $categoryId
     * @param array $results
     * @return int
     */
    public function getNumberOfCategorizedProducts(int $categoryId, array $results): int
    {
        $productIds = array_column($results, 'plu');

        return $this->em
            ->createQueryBuilder()
            ->update(Product::class, 'p')
            ->set('p.category_id', $categoryId)
            ->andWhere('p.product_id in (:productIds)')
            ->setParameters(['productIds' => $productIds])
            ->getQuery()
            ->execute();
    }
}