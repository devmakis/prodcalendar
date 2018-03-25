<?php

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Clients\DataGovClient;
use Devmakis\ProdCalendar\Exceptions\CalendarException;
use Devmakis\ProdCalendar\Clients\ClientException;

/**
 * Class Calendar производственный календарь
 * @package Devmakis\ProdCalendar
 */
class Calendar
{
    /**
     * @var DataGovClient
     */
    private $client;

    /**
     * @var Year[]
     */
    private $years = [];

    /**
     * Calendar constructor.
     * @param DataGovClient $client
     */
    public function __construct(DataGovClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param $numberY
     * @return Year
     * @throws CalendarException
     * @throws ClientException
     */
    public function getYear($numberY)
    {
        $numberY = (int)$numberY;

        if (!isset($this->years[$numberY])) {
            $this->years[$numberY] = $this->client->getYear($numberY);
        }

        return $this->years[$numberY];
    }

    /**
     * Найти день производственного календаря
     * @param \DateTime $date
     * @return Month
     * @throws CalendarException
     * @throws ClientException
     */
    public function findMonth(\DateTime $date)
    {
        $y = $date->format('Y');
        $m = $date->format('n');
        $month = $this->getYear($y)->getMonth($m);

        return $month;
    }

    /**
     * Найти день производственного календаря
     * @param \DateTime $date
     * @return Day|null
     * @throws CalendarException
     * @throws ClientException
     */
    public function findDay(\DateTime $date)
    {
        $month = $this->findMonth($date);
        $d = $date->format('j');

        try {
            $day = $month->findNonWorkingDay($d);
        } catch (CalendarException $e) {
            $day = $month->findPreHolidayDay($d);
        }

        return $day;
    }

    /**
     * Проверить является ли день праздничным
     * @param \DateTime $date
     * @return bool
     * @throws CalendarException
     * @throws ClientException
     */
    public function isHoliday(\DateTime $date)
    {
        return $this->findDay($date) instanceof Holiday;
    }

    /**
     * Проверить является ли день предпраздничным
     * @param \DateTime $date
     * @return bool
     * @throws CalendarException
     * @throws ClientException
     */
    public function isPreHoliday(\DateTime $date)
    {
        return $this->findDay($date) instanceof PreHolidayDay;
    }

    /**
     * Проверить является ли день выходным
     * @param \DateTime $date
     * @return bool
     * @throws CalendarException
     * @throws ClientException
     */
    public function isWeekend(\DateTime $date)
    {
        return $this->findDay($date) instanceof Weekend;
    }

    /**
     * Проверить является ли день нерабочим
     * @param \DateTime $date
     * @return bool
     * @throws CalendarException
     * @throws ClientException
     */
    public function isNonWorking(\DateTime $date)
    {
        return $this->isWeekend($date) || $this->isHoliday($date);
    }

    /**
     * Подсчитать количество рабочих дней за период
     * @param \DateTime $begin
     * @param \DateTime $end
     * @return int
     * @throws CalendarException
     * @throws ClientException
     */
    public function countWorkingDaysForPeriod(\DateTime $begin, \DateTime $end)
    {
        /**
         * @var null|Month $prevMonth
         * @var \DateTime $dateD
         * @var \DateTime $dateD
         */
        $count = 0;
        $monthBegin = $this->findMonth($begin);
        $monthEnd = $this->findMonth($end);

        $beginM = clone $begin;
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);
        $prevMonth = null;

        foreach ($periodM as $date) {
            /**
             * @var \DateTime $date
             */
            $month = $this->findMonth($date);

            if ($prevMonth && $month->getNumberM() == $prevMonth->getNumberM()) {
                continue;
            } elseif ($month->getNumberM() == $monthBegin->getNumberM()) {
                $endD = clone $date;
                $endD = $endD->modify('last day of this month');;

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay((int)$dateD->format('j'));
                    } catch (CalendarException $e) {
                        $count++;
                    }
                }
            } elseif ($month->getNumberM() == $monthEnd->getNumberM()) {
                $beginD = clone $date;
                $beginD = $beginD->modify('first day of this month');

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($beginD, $intervalD, $end);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay((int)$dateD->format('j'));
                    } catch (CalendarException $e) {
                        $count++;
                    }
                }
            } else {
                $count += $month->countWorkingDay();
            }

            $prevMonth = $month;
        }

        return $count;
    }

    /**
     * Подсчитать количество нерабочих дней за период
     * @param \DateTime $begin
     * @param \DateTime $end
     * @return int
     * @throws CalendarException
     * @throws ClientException
     */
    public function countNonWorkingDaysForPeriod(\DateTime $begin, \DateTime $end)
    {
        /**
         * @var null|Month $prevMonth
         * @var \DateTime $dateD
         * @var \DateTime $dateD
         */
        $count = 0;
        $monthBegin = $this->findMonth($begin);
        $monthEnd = $this->findMonth($end);

        $beginM = clone $begin;
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);
        $prevMonth = null;

        foreach ($periodM as $date) {
            /**
             * @var \DateTime $date
             */
            $month = $this->findMonth($date);

            if ($prevMonth && $month->getNumberM() == $prevMonth->getNumberM()) {
                continue;
            } elseif ($month->getNumberM() == $monthBegin->getNumberM()) {
                $endD = clone $date;
                $endD = $endD->modify('last day of this month');;

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay((int)$dateD->format('j'));
                        $count++;
                    } catch (CalendarException $e) {
                        continue;
                    }
                }
            } elseif ($month->getNumberM() == $monthEnd->getNumberM()) {
                $beginD = clone $date;
                $beginD = $beginD->modify('first day of this month');

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($beginD, $intervalD, $end);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay((int)$dateD->format('j'));
                        $count++;
                    } catch (CalendarException $e) {
                        continue;
                    }
                }
            } else {
                $count += $month->countNonWorkingDay();
            }

            $prevMonth = $month;
        }

        return $count;
    }
}
