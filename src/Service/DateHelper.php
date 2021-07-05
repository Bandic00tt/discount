<?php
namespace App\Service;

use App\Entity\DiscountHistory;
use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class DateHelper
{
    /**
     * @param DiscountHistory[] $history
     * @return array
     */
    public function getDiscountYears(array $history): array
    {
        $result = [];
        foreach ($history as $item) {
            $yearBegin = date('Y', $item->getDateBegin());
            $yearEnd = date('Y', $item->getDateEnd());

            $result[$item->getProductId()][] = $yearBegin;
            $result[$item->getProductId()][] = $yearEnd;
        }

        // Проходимся еще раз по годам со скидками, убираем дубликаты и сортируем
        foreach ($result as $productId => $years) {
            $years = array_unique($years);
            sort($years);
            $result[$productId] = $years;
        }

        return $result;
    }

    /**
     * @param string $year
     * @param DiscountHistory[] $history
     * @return array
     * @throws Exception
     */
    public function getDiscountDates(string $year, array $history): array
    {
        $result = [];
        foreach ($history as $item) {
            $result[$item->getProductId()][] = $this->getDatesFromRange(
                new DateTime(date('Y-m-d', $item->getDateBegin())),
                new DateTime(date('Y-m-d', $item->getDateEnd()))
            );
        }

        foreach ($result as $productId => $dateRanges) {
            $productDates = [];
            foreach ($dateRanges as $dateRange) {
                foreach ($dateRange as $date) {
                    $cond = date('Y', strtotime($date)) === $year
                        || date('Y', strtotime($date)) === (string)((int) $year - 1)
                        || date('Y', strtotime($date)) === (string)((int) $year + 1);

                    if ($cond) {
                        $productDates[] = $date;
                    }
                }
            }

            $result[$productId] = $productDates;
        }

        return $result;
    }

    /**
     * @param int $year
     * @return array
     * @throws Exception
     */
    public function getYearDates(int $year): array
    {
        $start = new DateTime($year .'-01-01');
        $finish = new DateTime($year .'-12-31');

        return $this->completeDates(
            $this->getDatesFromRange($start, $finish)
        );
    }

    /**
     * @param DateTime $start
     * @param DateTime $finish
     * @return array
     */
    public function getDatesFromRange(DateTime $start, DateTime $finish): array
    {
        $finish->modify('+1 day');

        $interval = new DateInterval('P1D');
        $range = new DatePeriod($start, $interval, $finish);
        $dates = [];

        foreach ($range as $dateItem) {
            $dates[] = $dateItem->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * Дополняем диапазон дат недостающими днями недели в начале и в конце
     * @param array $dates
     * @return array
     * @throws Exception
     */
    private function completeDates(array $dates): array
    {
        $firstDate = $dates[0];
        for ($i = date('N', strtotime($firstDate)); $i > 1; $i--) {
            $dateBefore = (new DateTime($dates[0]))
                ->modify('-1 day')
                ->format('Y-m-d');

            array_unshift($dates, $dateBefore);
        }

        $lastDate = end($dates);
        for ($i = date('N', strtotime($lastDate)); $i < 7; $i++) {
            $dateAfter = (new DateTime(end($dates)))
                ->modify('+1 day')
                ->format('Y-m-d');

            $dates[] = $dateAfter;
        }

        return $dates;
    }
}