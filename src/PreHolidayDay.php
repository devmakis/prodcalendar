<?php

namespace Devmakis\ProdCalendar;

/**
 * Class PreHolidayDay предпраздничный день
 * @package Devmakis\ProdCalendar
 */
class PreHolidayDay extends Day
{
    /**
     * PreHolidayDay constructor.
     * @param int $numberD
     * @param $numberM
     * @param $numberY
     */
    public function __construct($numberD, $numberM, $numberY)
    {
        parent::__construct($numberD, $numberM, $numberY);
        $this->description = 'Предпраздничный день';
    }
}
