<?php


namespace App\Service\Shop\Five;

use App\Entity\Discount;
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
    public function saveDiscountData(array $results): int
    {
        $total = 0;
        foreach ($results as $result) {
            $entity = new Discount();
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

    public function clearDiscountData()
    {
        $this->em->createQueryBuilder()
            ->delete(Discount::class, 'd')
            ->getQuery()
            ->execute();
    }
}