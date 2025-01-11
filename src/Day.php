<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar;

class Day
{
    public const array NAME_DAYS_RU = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];

    protected ?string $description;

    public function __construct(
        protected int $numberD,
        protected int $numberM,
        protected int $numberY
    ) {}

    public function getNumberD(): int
    {
        return $this->numberD;
    }

    public function getNumberM(): int
    {
        return $this->numberM;
    }

    public function getNumberY(): int
    {
        return $this->numberY;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function getDateTime(): \DateTime
    {
        return new \DateTime($this->__toString());
    }

    public function __toString(): string
    {
        return $this->numberY . '-' .
            \str_pad((string) $this->numberM, 2, '0', STR_PAD_LEFT) . '-' .
            \str_pad((string) $this->numberD, 2, '0', STR_PAD_LEFT);
    }
}
