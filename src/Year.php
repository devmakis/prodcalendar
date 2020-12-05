<?php

namespace Devmakis\ProdCalendar;

use DateTime;
use Devmakis\ProdCalendar\Exceptions\CalendarException;
use Exception;

/**
 * Class Year год производственного календаря
 * @package Devmakis\ProdCalendar
 */
class Year
{
    /**
     * @var string номер года
     */
    protected $numberY;

    /**
     * @var Month[] массив объектов месяцев
     */
    protected $months = [];

    /**
     * @var int всего рабочих дней в году
     */
    protected $totalWorkingDays;

    /**
     * @var int всего праздничных и выходных дней в году
     */
    protected $totalNonworkingDays;

    /**
     * @var int количество рабочих часов при 40-часовой рабочей неделе
     */
    protected $numWorkingHours40;

    /**
     * @var int количество рабочих часов при 36-часовой рабочей неделе
     */
    protected $numWorkingHours36;

    /**
     * @var int количество рабочих часов при 24-часовой рабочей неделе
     */
    protected $numWorkingHours24;

    /**
     * Year constructor.
     * @param string $number номер года
     * @param array $months
     */
    public function __construct($number, array $months)
    {
        $this->numberY = (string)$number;
        $this->months = $months;
    }

    /**
     * @return string
     */
    public function getNumberY()
    {
        return $this->numberY;
    }

    /**
     * @return Month[]
     */
    public function getMonths()
    {
        return $this->months;
    }

    /**
     * @return int
     */
    public function getTotalWorkingDays()
    {
        return $this->totalWorkingDays;
    }

    /**
     * @return int
     */
    public function getTotalNonworkingDays()
    {
        return $this->totalNonworkingDays;
    }

    /**
     * @return int
     */
    public function getNumWorkingHours40()
    {
        return $this->numWorkingHours40;
    }

    /**
     * @return int
     */
    public function getNumWorkingHours36()
    {
        return $this->numWorkingHours36;
    }

    /**
     * @return int
     */
    public function getNumWorkingHours24()
    {
        return $this->numWorkingHours24;
    }

    /**
     * @param int $numWorkingHours40
     */
    public function setNumWorkingHours40($numWorkingHours40)
    {
        $this->numWorkingHours40 = (int)$numWorkingHours40;
    }

    /**
     * @param int $numWorkingHours36
     */
    public function setNumWorkingHours36($numWorkingHours36)
    {
        $this->numWorkingHours36 = (int)$numWorkingHours36;
    }

    /**
     * @param int $numWorkingHours24
     */
    public function setNumWorkingHours24($numWorkingHours24)
    {
        $this->numWorkingHours24 = (int)$numWorkingHours24;
    }


    /**
     * Получить месяц
     * @param string $m
     * @return Month
     * @throws CalendarException
     */
    public function getMonth($m)
    {
        if (!isset($this->months[$m])) {
            throw new CalendarException("Month «{$m}» not found in production calendar");
        }

        return $this->months[$m];
    }

    /**
     * Подсчитать количество нерабочих дней в году
     * @return int
     */
    public function countNonWorkingDays()
    {
        $count = 0;

        foreach ($this->months as $month) {
            $count += $month->countNonWorkingDays();
        }

        return $count;
    }

    /**
     * Подсчитать количество рабочих дней в году
     * @return int
     * @throws Exception
     */
    public function countWorkingDays()
    {
        $count = 0;

        foreach ($this->months as $month) {
            $count += $month->countWorkingDays();
        }

        return $count;
    }

    /**
     * Подсчитать количество календарных дней в году
     * @return int|string
     */
    public function countCalendarDays()
    {
        return (int)(new DateTime("31-12-{$this->numberY}"))->format('z') + 1;
    }
}
