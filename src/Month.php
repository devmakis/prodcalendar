<?php

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Exceptions\CalendarException;

/**
 * Class Month - месяц производственного календаря
 * @package Devmakis\ProdCalendar
 */
class Month
{
    /**
     * @var string номер месяца
     */
    protected $numberM;

    /**
     * @var int номер года
     */
    protected $numberY;

    /**
     * @var Day[] нерабочие дни в месяце
     */
    protected $nonWorkingDays = [];

    /**
     * @var PreHolidayDay[] предпраздничные дни
     */
    protected $preHolidayDays = [];

    /**
     * Month constructor.
     * @param string $numberM
     * @param $numberY
     * @param Day[] $nonWorkingDays
     * @param PreHolidayDay[] $preHolidayDays
     */
    public function __construct($numberM, $numberY, array $nonWorkingDays, array $preHolidayDays)
    {
        if (strlen($numberM) == 1) {
            $numberM = '0' . $numberM;
        }

        $this->numberM = (string)$numberM;
        $this->numberY = (string)$numberY;
        $this->nonWorkingDays = $nonWorkingDays;
        $this->preHolidayDays = $preHolidayDays;
    }

    /**
     * @return string
     */
    public function getNumberM()
    {
        return $this->numberM;
    }

    /**
     * @return int
     */
    public function getNumberY()
    {
        return $this->numberY;
    }

    /**
     * @return Weekend[]
     */
    public function getNonWorkingDays()
    {
        return $this->nonWorkingDays;
    }

    /**
     * @return PreHolidayDay[]
     */
    public function getPreHolidayDays()
    {
        return $this->preHolidayDays;
    }

    /**
     * Найти нерабочий день
     * @param $d
     * @return Day
     * @throws CalendarException
     */
    public function findNonWorkingDay($d)
    {
        if (!isset($this->nonWorkingDays[$d])) {
            throw new CalendarException("Day «{$d}» not found");
        }

        return $this->nonWorkingDays[$d];
    }

    /**
     * Найти предпраздничный день
     * @param $d
     * @return Day
     * @throws CalendarException
     */
    public function findPreHolidayDay($d)
    {
        $d = (int)$d;

        if (!isset($this->preHolidayDays[$d])) {
            throw new CalendarException("Day «{$d}» not found");
        }

        return $this->preHolidayDays[$d];
    }

    /**
     * Подсчитать количество нерабочих дней в месяце
     * @return int
     */
    public function countNonWorkingDays()
    {
        return count($this->nonWorkingDays);
    }

    /**
     * Подсчитать количество рабочих дней в месяце
     * @return int
     */
    public function countWorkingDays()
    {
        $countDays = (new \DateTime("01-{$this->numberM}-{$this->numberY}"))->format('t');
        return $countDays - count($this->nonWorkingDays);
    }
}
