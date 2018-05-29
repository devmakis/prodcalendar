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
     * Найти день из производственного календаря (нерабочий или предпраздничный)
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
     * @throws ClientException
     */
    public function isHoliday(\DateTime $date)
    {
        try {
            return $this->findDay($date) instanceof Holiday;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Проверить является ли день предпраздничным
     * @param \DateTime $date
     * @return bool
     * @throws ClientException
     */
    public function isPreHoliday(\DateTime $date)
    {
        try {
            return $this->findDay($date) instanceof PreHolidayDay;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Проверить является ли день выходным
     * @param \DateTime $date
     * @return bool
     * @throws ClientException
     */
    public function isWeekend(\DateTime $date)
    {
        try {
            return $this->findDay($date) instanceof Weekend;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Проверить является ли день нерабочим
     * @param \DateTime $date
     * @return bool
     * @throws ClientException
     */
    public function isNonWorking(\DateTime $date)
    {
        if ($this->isWeekend($date)) {
            return true;
        } elseif ($this->isHoliday($date)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Подсчитать количество рабочих дней за период
     * @param \DateTime $begin начальная дата периода
     * @param \DateTime $end конечная дата периода
     * @param bool $excludeBegin не учитывать начальную дату
     * @param bool $excludeEnd не учитывать конечную дату
     * @return int количество рабочих дней за период
     * @throws CalendarException
     * @throws ClientException
     */
    public function countWorkingDaysForPeriod(\DateTime $begin, \DateTime $end, $excludeBegin = false, $excludeEnd = false)
    {
        /**
         * @var \DateTime $dateM
         * @var \DateTime $dateD
         */
        $count = 0;
        $begin = clone $begin;
        $begin->setTime(0, 0, 0);
        $end = clone $end;
        $excludeEnd ? $end->setTime(0, 0, 0) : $end->setTime(23, 59, 59);

        if ($begin >= $end) {
            throw new ClientException('Invalid time period');
        }

        $monthBegin = $this->findMonth($begin);
        $monthEnd = $this->findMonth($end);

        $beginM = clone $begin;
        // Начало периода сбрасываем на начало месяца чтобы интервал в 1 месяц не пропустил какой-либо месяц
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);

        foreach ($periodM as $dateM) {
            $month = $this->findMonth($dateM);

            // Если первый месяц из периода
            if ($month->getNumberY() == $monthBegin->getNumberY() &&
                $month->getNumberM() == $monthBegin->getNumberM()
            ) {
                // Если начало и конец периода это день из одного и того же месяца и года
                if ($monthBegin->getNumberY() == $monthEnd->getNumberY() &&
                    $monthBegin->getNumberM() == $monthEnd->getNumberM()
                ) {
                    $endD = $end;
                } else {
                    $endD = clone $dateM;
                    // Устанавливаем конец периода последним днем месяца
                    $endD->modify('last day of this month')->setTime(23, 59, 59);
                }

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD, (int)$excludeBegin);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay($dateD->format(self::FORMAT_DAY));
                    } catch (CalendarException $e) {
                        $count++;
                    }
                }
            // Если последний месяц из периода
            } elseif ($month->getNumberY() == $monthEnd->getNumberY() &&
                $month->getNumberM() == $monthEnd->getNumberM()
            ) {
                $beginD = clone $dateM;
                // Т.к. это последний месяц периода, то отсчет начинаем с первого дня месяца
                $beginD->modify('first day of this month');

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($beginD, $intervalD, $end);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay($dateD->format(self::FORMAT_DAY));
                    } catch (CalendarException $e) {
                        $count++;
                    }
                }
            // Промежуточные месяцы (полные)
            } else {
                $count += $month->countWorkingDays();
            }
        }

        return $count;
    }

    /**
     * Подсчитать количество нерабочих дней за период
     * @param \DateTime $begin начальная дата периода
     * @param \DateTime $end конечная дата периода
     * @param bool $excludeBegin не учитывать начальную дату
     * @param bool $excludeEnd не учитывать конечную дату
     * @return int количество рабочих дней за период
     * @throws CalendarException
     * @throws ClientException
     */
    public function countNonWorkingDaysForPeriod(\DateTime $begin, \DateTime $end, $excludeBegin = false, $excludeEnd = false)
    {
        /**
         * @var \DateTime $dateM
         * @var \DateTime $dateD
         */
        $count = 0;
        $begin = clone $begin;
        $begin->setTime(0, 0, 0);
        $end = clone $end;
        $excludeEnd ? $end->setTime(0, 0, 0) : $end->setTime(23, 59, 59);

        if ($begin >= $end) {
            throw new ClientException('Invalid time period');
        }

        $monthBegin = $this->findMonth($begin);
        $monthEnd = $this->findMonth($end);

        $beginM = clone $begin;
        // Начало периода сбрасываем на начало месяца чтобы интервал в 1 месяц не пропустил какой-либо месяц
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);

        foreach ($periodM as $dateM) {
            $month = $this->findMonth($dateM);

            // Если первый месяц из периода
            if ($month->getNumberY() == $monthBegin->getNumberY() &&
                $month->getNumberM() == $monthBegin->getNumberM()
            ) {
                // Если начало и конец периода это день из одного и того же месяца и года
                if ($monthBegin->getNumberY() == $monthEnd->getNumberY() &&
                    $monthBegin->getNumberM() == $monthEnd->getNumberM()
                ) {
                    $endD = $end;
                } else {
                    $endD = clone $dateM;
                    // Устанавливаем конец периода последним днем месяца
                    $endD->modify('last day of this month')->setTime(23, 59, 59);
                }

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD, (int)$excludeBegin);

                foreach ($periodD as $dateD) {
                    try {
                        $month->findNonWorkingDay($dateD->format(self::FORMAT_DAY));
                        $count++;
                    } catch (CalendarException $e) {
                        continue;
                    }
                }
            // Если последний месяц из периода
            } elseif ($month->getNumberY() == $monthEnd->getNumberY() &&
                $month->getNumberM() == $monthEnd->getNumberM()
            ) {
                $beginD = clone $dateM;
                // Т.к. это последний месяц периода, то отсчет начинаем с первого дня месяца
                $beginD->modify('first day of this month');

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
            // Промежуточные месяцы (полные)
            } else {
                $count += $month->countNonWorkingDays();
            }
        }

        return $count;
    }
}
