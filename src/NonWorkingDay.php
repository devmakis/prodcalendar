<?php

namespace Devmakis\ProdCalendar;

/**
 * Class NonWorkingDay - не рабочий день
 * @package Devmakis\ProdCalendar
 */
class NonWorkingDay extends Day
{
    /**
     * @var bool является ли этот день обычным выходным
     */
    protected $isWeekend;

    /**
     * @var bool является ли этот день праздничным
     */
    protected $isHoliday;

    /**
     * NonWorkingDay constructor.
     * @param int $number
     */
    public function __construct($number)
    {
        parent::__construct($number);
    }

    /**
     * @return bool
     */
    public function isWeekend()
    {
        return $this->isWeekend;
    }

    /**
     * @return bool
     */
    public function isHoliday()
    {
        return $this->isHoliday;
    }
}
