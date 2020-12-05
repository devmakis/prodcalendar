<?php

namespace Devmakis\ProdCalendar;

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use Devmakis\ProdCalendar\Clients\Exceptions\ClientException;
use Devmakis\ProdCalendar\Clients\DataGovClient;
use Devmakis\ProdCalendar\Clients\IClient;
use Devmakis\ProdCalendar\Exceptions\CalendarException;
use Exception;

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
     * @param DateTimeInterface $date
     * @return Month
     * @throws CalendarException
     * @throws ClientException
     */
    public function findMonth(DateTimeInterface $date)
    {
        $y = $date->format(self::FORMAT_YEAR);
        $m = $date->format(self::FORMAT_MONTH);

        return $this->getYear($y)->getMonth($m);
    }

    /**
     * Найти день из производственного календаря (нерабочий или предпраздничный)
     * @param DateTimeInterface $date
     * @return Day|null
     * @throws CalendarException
     * @throws ClientException
     */
    public function findDay(DateTimeInterface $date)
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
     * @param DateTimeInterface $date
     * @return bool
     * @throws ClientException
     */
    public function isHoliday(DateTimeInterface $date)
    {
        try {
            return $this->findDay($date) instanceof Holiday;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Проверить является ли день предпраздничным
     * @param DateTimeInterface $date
     * @return bool
     * @throws ClientException
     */
    public function isPreHoliday(DateTimeInterface $date)
    {
        try {
            return $this->findDay($date) instanceof PreHolidayDay;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Проверить является ли день выходным
     * @param DateTimeInterface $date
     * @return bool
     * @throws ClientException
     */
    public function isWeekend(DateTimeInterface $date)
    {
        try {
            return $this->findDay($date) instanceof Weekend;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Проверить является ли день перенесенным праздником
     * @param DateTimeInterface $date
     * @return bool
     * @throws ClientException
     */
    public function isTransferredHoliday(DateTimeInterface $date)
    {
        try {
            return $this->findDay($date) instanceof TransferredHoliday;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Проверить является ли день нерабочим
     * @param DateTimeInterface $date
     * @return bool
     * @throws ClientException
     */
    public function isNonWorking(DateTimeInterface $date)
    {
        try {
            return $this->findDay($date) instanceof NonWorkingDay;
        } catch (CalendarException $e) {
            return false;
        }
    }

    /**
     * Подсчитать количество рабочих дней за период
     * @param DateTimeInterface $begin начальная дата периода
     * @param DateTimeInterface $end конечная дата периода
     * @param bool $excludeBegin не учитывать начальную дату
     * @param bool $excludeEnd не учитывать конечную дату
     * @return int количество рабочих дней за период
     * @throws CalendarException
     * @throws ClientException
     * @throws Exception
     */
    public function countWorkingDaysForPeriod(
        DateTimeInterface $begin,
        DateTimeInterface $end,
        $excludeBegin = false,
        $excludeEnd = false
    ) {
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
        $intervalM = DateInterval::createFromDateString('1 month');
        $periodM = new DatePeriod($beginM, $intervalM, $end);

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

                $intervalD = DateInterval::createFromDateString('1 day');
                $periodD = new DatePeriod($begin, $intervalD, $endD, (int)$excludeBegin);

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

                $intervalD = DateInterval::createFromDateString('1 day');
                $periodD = new DatePeriod($beginD, $intervalD, $end);

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
     * @param DateTimeInterface $begin начальная дата периода
     * @param DateTimeInterface $end конечная дата периода
     * @param bool $excludeBegin не учитывать начальную дату
     * @param bool $excludeEnd не учитывать конечную дату
     * @return int количество рабочих дней за период
     * @throws CalendarException
     * @throws ClientException
     */
    public function countNonWorkingDaysForPeriod(
        DateTimeInterface $begin,
        DateTimeInterface $end,
        $excludeBegin = false,
        $excludeEnd = false
    ) {
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
        $intervalM = DateInterval::createFromDateString('1 month');
        $periodM = new DatePeriod($beginM, $intervalM, $end);

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

                $intervalD = DateInterval::createFromDateString('1 day');
                $periodD = new DatePeriod($begin, $intervalD, $endD, (int)$excludeBegin);

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

                $intervalD = DateInterval::createFromDateString('1 day');
                $periodD = new DatePeriod($beginD, $intervalD, $end);

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

    /**
     * Найти ближайшую рабочую дату (включая сегодняшнюю)
     * @param DateTimeInterface|null $date
     * @return DateTime
     * @throws ClientException
     */
    public function nearestWorkingDate(DateTimeInterface $date = null)
    {
        if ($date === null) {
            $date = (new DateTime());
        }

        while ($this->isNonWorking($date)) {
            $interval = DateInterval::createfromdatestring('+1 day');
            $date->add($interval);
        }

        return $date;
    }
}
