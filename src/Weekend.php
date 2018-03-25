<?php

namespace Devmakis\ProdCalendar;

/**
 * Class Weekend выходной день
 * @package Devmakis\ProdCalendar
 */
class Weekend extends Day
{
    /**
     * Weekend constructor.
     * @param $numberD
     * @param $numberM
     * @param $numberY
     */
    public function __construct($numberD, $numberM, $numberY)
    {
        parent::__construct($numberD, $numberM, $numberY);
        $this->description = 'Выходной день';

        $nDayWeek = $this->getDateTime()->format('N');

        if ($nDayWeek == 6 || $nDayWeek == 7) {
            $this->description .= ' (' . self::NAME_DAYS_RU[$nDayWeek] . ')';
        } else {
            $this->description .= ' (переносенный)';
        }
    }
}
