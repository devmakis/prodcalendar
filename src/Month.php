<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Exceptions\CalendarException;

class Month
{
    /**
     * @param array<Day> $nonWorkingDays
     * @param array<PreHolidayDay> $preHolidayDays
     */
    public function __construct(
        protected int $numberM,
        protected int $numberY,
        protected array $nonWorkingDays,
        protected array $preHolidayDays
    ) {}

    public function getNumberM(): int
    {
        return $this->numberM;
    }

    public function getNumberY(): int
    {
        return $this->numberY;
    }

    /**
     * @return array<Day>
     */
    public function getNonWorkingDays(): array
    {
        return $this->nonWorkingDays;
    }

    /**
     * @return array<PreHolidayDay>
     */
    public function getPreHolidayDays(): array
    {
        return $this->preHolidayDays;
    }

    public function getNonWorkingDay(int $dayNumber): ?Day
    {
        return $this->nonWorkingDays[$dayNumber] ?? null;
    }

    /**
     * @throws CalendarException
     */
    public function findNonWorkingDay(int $dayNumber): Day
    {
        if (!isset($this->nonWorkingDays[$dayNumber])) {
            throw new CalendarException('Day «' . $dayNumber . '» not found');
        }

        return $this->nonWorkingDays[$dayNumber];
    }

    public function getPreHolidayDay(int $dayNumber): ?PreHolidayDay
    {
        return $this->preHolidayDays[$dayNumber] ?? null;
    }

    /**
     * @throws CalendarException
     */
    public function findPreHolidayDay(int $dayNumber): PreHolidayDay
    {
        if (!isset($this->preHolidayDays[$dayNumber])) {
            throw new CalendarException('Day «' . $dayNumber . '» not found');
        }

        return $this->preHolidayDays[$dayNumber];
    }

    public function countNonWorkingDays(): int
    {
        return \count($this->nonWorkingDays);
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function countWorkingDays(): int
    {
        $countDays = (int) (new \DateTime('01-' . $this->__toString()))->format('t');
        return $countDays - \count($this->nonWorkingDays);
    }

    public function __toString(): string
    {
        return \str_pad((string) $this->numberM, 2, '0', STR_PAD_LEFT) . '-' . $this->numberY;
    }
}
