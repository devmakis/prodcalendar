<?php

namespace Devmakis\ProdCalendar;

use Exception;

/**
 * Class Weekend обычный выходной день (сб, вск)
 * @package Devmakis\ProdCalendar
 */
class Weekend extends Day implements NonWorkingDay
{
    /**
     * Weekend constructor.
     * @param $numberD
     * @param $numberM
     * @param $numberY
     * @throws Exception
     */
    public function __construct($numberD, $numberM, $numberY)
    {
        parent::__construct($numberD, $numberM, $numberY);
        $nDayWeek = $this->getDateTime()->format('N');
        $this->description = 'Выходной день (' . self::NAME_DAYS_RU[$nDayWeek] . ')';
    }
}
