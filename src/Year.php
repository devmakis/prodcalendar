<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Exceptions\CalendarException;

class Year
{
    /**
     * Number of working hours with a 40-hour work week
     */
    protected ?float $numberWorkingHours40 = null;

    /**
     * Number of working hours with a 40-hour work week
     */
    protected ?float $numberWorkingHours36 = null;

    /**
     * Number of working hours in a 24-hour work week
     */
    protected ?float $numberWorkingHours24 = null;

    /**
     * Number of working hours with a 40-hour work week
     * @deprecated
     * @see Year::$numberWorkingHours40
     */
    protected int $numWorkingHours40;

    /**
     * Number of working hours with a 36-hour work week
     * @deprecated
     * @see Year::$numberWorkingHours36
     */
    protected int $numWorkingHours36;

    /**
     * Number of working hours in a 24-hour work week
     * @deprecated
     * @see Year::$numberWorkingHours24
     */
    protected int $numWorkingHours24;

    /**
     * @param array<int, Month> $months
     */
    public function __construct(
        protected int $numberY,
        protected array $months
    ) {}

    public function getNumberY(): int
    {
        return $this->numberY;
    }

    /**
     * @return array<Month>
     */
    public function getMonths(): array
    {
        return $this->months;
    }

    public function getNumberWorkingHours40(): ?float
    {
        return $this->numberWorkingHours40;
    }

    public function setNumberWorkingHours40(float $numberWorkingHours40): void
    {
        $this->numberWorkingHours40 = $numberWorkingHours40;
    }

    public function getNumberWorkingHours36(): ?float
    {
        return $this->numberWorkingHours36;
    }

    public function setNumberWorkingHours36(float $numberWorkingHours36): void
    {
        $this->numberWorkingHours36 = $numberWorkingHours36;
    }

    public function getNumberWorkingHours24(): ?float
    {
        return $this->numberWorkingHours24;
    }

    public function setNumberWorkingHours24(float $numberWorkingHours24): void
    {
        $this->numberWorkingHours24 = $numberWorkingHours24;
    }

    /**
     * @deprecated
     * @see Year::getNumberWorkingHours40
     */
    public function getNumWorkingHours40(): int
    {
        return $this->numWorkingHours40;
    }

    /**
     * @deprecated
     * @see Year::getNumberWorkingHours36
     */
    public function getNumWorkingHours36(): int
    {
        return $this->numWorkingHours36;
    }

    /**
     * @deprecated
     * @see Year::getNumberWorkingHours24
     */
    public function getNumWorkingHours24(): int
    {
        return $this->numWorkingHours24;
    }

    /**
     * @deprecated
     * @see Year::setNumberWorkingHours40
     */
    public function setNumWorkingHours40(int $numWorkingHours40): void
    {
        $this->numWorkingHours40 = $numWorkingHours40;
    }

    /**
     * @deprecated
     * @see Year::setNumberWorkingHours36
     */
    public function setNumWorkingHours36(int $numWorkingHours36): void
    {
        $this->numWorkingHours36 = $numWorkingHours36;
    }

    /**
     * @deprecated
     * @see Year::setNumberWorkingHours24
     */
    public function setNumWorkingHours24(int $numWorkingHours24): void
    {
        $this->numWorkingHours24 = $numWorkingHours24;
    }

    public function findMonth(int $monthNumber): ?Month
    {
        return $this->months[$monthNumber] ?? null;
    }

    /**
     * @throws CalendarException
     */
    public function getMonth(int $monthNumber): Month
    {
        if (!isset($this->months[$monthNumber])) {
            throw new CalendarException('Month «' . $monthNumber . '» not found in production calendar');
        }

        return $this->months[$monthNumber];
    }

    public function countNonWorkingDays(): ?int
    {
        $totalNonworkingDays = 0;

        foreach ($this->months as $month) {
            $totalNonworkingDays += \count($month->getNonWorkingDays());
        }

        return $totalNonworkingDays;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function countWorkingDays(): int
    {
        $totalWorkingDays = 0;

        foreach ($this->months as $month) {
            $totalWorkingDays += $month->countWorkingDays();
        }

        return $totalWorkingDays;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function countCalendarDays(): int
    {
        return (int) (new \DateTime('31-12-' . $this->numberY))->format('z') + 1;
    }

    public function __toString(): string
    {
        return (string) $this->numberY;
    }
}
