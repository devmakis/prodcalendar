<?php

namespace Devmakis\ProdCalendar;

/**
 * Class PreHolidayDay - предпраздничный день
 * @package Devmakis\ProdCalendar
 */
class PreHolidayDay extends Day
{
    /**
     * PreHolidayDay constructor.
     * @param int $number
     */
    public function __construct($number)
    {
        parent::__construct($number);
    }
}
