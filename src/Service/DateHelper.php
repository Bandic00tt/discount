<?php
namespace App\Service;

use DateInterval;
use DatePeriod;
use DateTime;
use Exception;

class DateHelper
{
    /**
     * @param int $year
     * @return array
     * @throws Exception
     */
    public function getYearDatesRange(int $year): array
    {
        $start = new DateTime($year .'-01-01');
        $finish = new DateTime($year .'-12-31');

        return $this->completeDates(
            $this->getDatesRange($start, $finish)
        );
    }

    /**
     * @param DateTime $start
     * @param DateTime $finish
     * @return array
     */
    public function getDatesRange(DateTime $start, DateTime $finish): array
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