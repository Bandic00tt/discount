<?php /** @noinspection DuplicatedCode */

namespace App\Service\Shop\Five;

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
    public const PITER_ID = 8169;
    public const KAZAN_ID = 8185;
    public const NN_ID = 8163;
    public const SAMARA_ID = 8131;
    public const ROSTOV_ID = 8278;
    public const VORONEZH_ID = 8234;
    public const VOLGOGRAD_ID = 8403;
    public const CHEBOKSARY_ID = 8238;
    public const NOVCHIK_ID = 8262;

    public const CITIES = [
        self::MOSCOW_ID => 'Москва',
        self::PITER_ID => 'Санкт-Петербург',
        self::KAZAN_ID => 'Казань',
        self::NN_ID => 'Нижний Новгород',
        self::SAMARA_ID => 'Самара',
        self::ROSTOV_ID => 'Ростов-на-Дону',
        self::VORONEZH_ID => 'Воронеж',
        self::VOLGOGRAD_ID => 'Волгоград',
        self::CHEBOKSARY_ID => 'Чебоксары',
        self::NOVCHIK_ID => 'Новочебоксарск',
    ];

    private EntityManagerInterface $em;

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
            $productId = (int)$result['plu'];
            if (in_array($productId, $existingProductIds, true)) {
                continue;
            }

            $entity = new Product();
            $entity->setProductId($productId);
            $entity->setName($result['name']);
            $entity->setImgLink($result['img_link']);
            $entity->setIsFavorited(false);
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
     * @return int[]
     */
    private function getExistingProductIds(): array
    {
        $res = $this->em->createQueryBuilder()
            ->select(['p.product_id'])
            ->from(Product::class, 'p')
            ->getQuery()
            ->getResult();

        return array_map(function($item) {
            return (int)$item['product_id'];
        }, $res);
    }

    /**
     * @param int $locationId
     * @param array $results
     * @return int
     */
    public function updateHistory(int $locationId, array $results): int
    {
        $total = 0;
        $existingDiscountIds = $this->getExistingDiscountIds();
        foreach ($results as $result) {
            $discountId = (int)$result['promo']['id'];
            if (in_array($discountId, $existingDiscountIds, true)) {
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
    private function getExistingDiscountIds(): array
    {
        $res = $this->em->createQueryBuilder()
            ->select(['dh.discount_id'])
            ->from(DiscountHistory::class, 'dh')
            ->getQuery()
            ->getResult();

        return array_map(function($item) {
            return (int)$item['discount_id'];
        }, $res);
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
            $entity->setSavedAt(time());

            $this->em->persist($entity);
            ++$total;
        }

        $this->em->flush();
        $this->em->clear();

        return $total;
    }
}