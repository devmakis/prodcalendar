<?php

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Clients\Exceptions\ClientException;
use Devmakis\ProdCalendar\Clients\DataGovClient;
use Devmakis\ProdCalendar\Clients\IClient;
use Devmakis\ProdCalendar\Exceptions\CalendarException;

/**
 * Class Calendar производственный календарь
 * @package Devmakis\ProdCalendar
 */
class Calendar
{
    /**
     * Формат года
     */
    const FORMAT_YEAR = 'Y';

    /**
     * Формат месяца
     */
    const FORMAT_MONTH = 'm';

    /**
     * Формат дня
     */
    const FORMAT_DAY = 'd';

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
     * @param IClient $client
     */
    public function __construct(IClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param $numberY
     * @return Year
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
     * Найти месяц из производственного календаря
     * @param \DateTime $date
     * @return Month
     * @throws CalendarException
     * @throws ClientException
     */
    public function findMonth(\DateTime $date)
    {
        $y = $date->format(self::FORMAT_YEAR);
        $m = $date->format(self::FORMAT_MONTH);
        $month = $this->getYear($y)->getMonth($m);

        return $month;
    }

    /**
     * Найти день из производственного календаря
     * @param \DateTime $date
     * @return Day|null
     * @throws CalendarException
     * @throws ClientException
     */
    public function findDay(\DateTime $date)
    {
        $month = $this->findMonth($date);
        $d = $date->format(self::FORMAT_DAY);

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
         * @var \DateTime $dateM
         * @var \DateTime $dateD
         */
        $count = 0;
        $monthBegin = $this->findMonth($begin);
        $monthEnd = $this->findMonth($end);

        $beginM = clone $begin;
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);

        foreach ($periodM as $dateM) {
            $month = $this->findMonth($dateM);

            if ($month->getNumberM() == $monthBegin->getNumberM()) { // если первый месяц из периода
                $endD = clone $dateM;
                $endD = $endD->modify('last day of this month');;

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay($dateD->format(self::FORMAT_DAY));
                    } catch (CalendarException $e) {
                        $count++;
                    }
                }
            } elseif ($month->getNumberM() == $monthEnd->getNumberM()) { // если последний месяц из периода
                $beginD = clone $dateM;
                $beginD = $beginD->modify('first day of this month');

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($beginD, $intervalD, $end);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay($dateD->format(self::FORMAT_DAY));
                    } catch (CalendarException $e) {
                        $count++;
                    }
                }
            } else { // промежуточные месяцы
                $count += $month->countWorkingDays();
            }
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
         * @var \DateTime $dateM
         * @var \DateTime $dateD
         */
        $count = 0;
        $monthBegin = $this->findMonth($begin);
        $monthEnd = $this->findMonth($end);

        $beginM = clone $begin;
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);

        foreach ($periodM as $dateM) {
            $month = $this->findMonth($dateM);

            if ($month->getNumberM() == $monthBegin->getNumberM()) { // если первый месяц из периода
                $endD = clone $dateM;
                $endD = $endD->modify('last day of this month');;

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay($dateD->format(self::FORMAT_DAY));
                        $count++;
                    } catch (CalendarException $e) {
                        continue;
                    }
                }
            } elseif ($month->getNumberM() == $monthEnd->getNumberM()) { // если последний месяц из периода
                $beginD = clone $dateM;
                $beginD = $beginD->modify('first day of this month');

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($beginD, $intervalD, $end);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay($dateD->format(self::FORMAT_DAY));
                        $count++;
                    } catch (CalendarException $e) {
                        continue;
                    }
                }
            } else { // промежуточные месяцы
                $count += $month->countNonWorkingDays();
            }
        }

        return $count;
    }
}
