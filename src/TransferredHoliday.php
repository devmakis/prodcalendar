<?php

namespace Devmakis\ProdCalendar;

/**
 * Class TransferredHoliday - перенесенный праздник
 * @package Devmakis\ProdCalendar
 */
class TransferredHoliday extends Day implements NonWorkingDay
{
    public function __construct($numberD, $numberM, $numberY)
    {
        // Определяем перенос ли это праздника
        parent::__construct($numberD, $numberM, $numberY);
        $this->description = 'Выходной день (перенесенный праздник)';
    }
}
