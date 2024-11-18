<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar;

class Weekend extends Day implements NonWorkingDay
{
    /**
     * @throws \DateMalformedStringException
     */
    public function __construct(int $numberD, int $numberM, int $numberY)
    {
        parent::__construct($numberD, $numberM, $numberY);
        $dayName = self::NAME_DAYS_RU[(int) (new \DateTime((string) $this))->format('N')];
        $this->description = 'Выходной день (' . $dayName . ')';
    }
}
