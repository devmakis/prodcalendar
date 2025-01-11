<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Clients\ClientExceptionInterface;
use Devmakis\ProdCalendar\Clients\IClient;
use Devmakis\ProdCalendar\Exceptions\CalendarException;

class Calendar
{
    /**
     * @deprecated
     */
    public const string FORMAT_YEAR = 'Y';

    /**
     * @deprecated
     */
    public const string FORMAT_MONTH = 'm';

    /**
     * @deprecated
     */
    public const string FORMAT_DAY = 'd';

    /**
     * @var array<int, Year>
     */
    protected array $years = [];

    public function __construct(
        protected IClient $client,
        protected bool $isCacheableYears = true
    ) {}

    /**
     * @throws ClientExceptionInterface
     */
    public function getYear(int $yearNumber): Year
    {
        if ($this->isCacheableYears) {
            if (!isset($this->years[$yearNumber])) {
                $this->years[$yearNumber] = $this->client->getYear($yearNumber);
            }

            return $this->years[$yearNumber];
        }

        return $this->client->getYear($yearNumber);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function getMonth(\DateTimeInterface $date): ?Month
    {
        $yearNumber = (int) $date->format('Y');
        $monthNumber = (int) $date->format('m');

        return $this->getYear($yearNumber)->findMonth($monthNumber);
    }

    /**
     * @throws CalendarException
     * @throws ClientExceptionInterface
     */
    public function findMonth(\DateTimeInterface $date): ?Month
    {
        $yearNumber = (int) $date->format('Y');
        $monthNumber = (int) $date->format('m');

        return $this->getYear($yearNumber)->getMonth($monthNumber);
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function getDay(\DateTimeInterface $date): ?Day
    {
        $month = $this->getMonth($date);
        $dayNumber = (int) $date->format('d');

        return $month->getNonWorkingDay($dayNumber) ?? $month->getPreHolidayDay($dayNumber);
    }

    /**
     * @throws CalendarException
     * @throws ClientExceptionInterface
     */
    public function findDay(\DateTimeInterface $date): Day
    {
        $month = $this->findMonth($date);
        $dayNumber = (int) $date->format('d');

        try {
            $day = $month->findNonWorkingDay($dayNumber);
        } catch (CalendarException $e) {
            $day = $month->findPreHolidayDay($dayNumber);
        }

        return $day;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function isHoliday(\DateTimeInterface $date): bool
    {
        return $this->getDay($date) instanceof Holiday;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function isPreHoliday(\DateTimeInterface $date): bool
    {
        return $this->getDay($date) instanceof PreHolidayDay;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function isWeekend(\DateTimeInterface $date): bool
    {
        return $this->getDay($date) instanceof Weekend;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function isTransferredHoliday(\DateTimeInterface $date): bool
    {
        return $this->getDay($date) instanceof TransferredHoliday;
    }

    /**
     * @throws ClientExceptionInterface
     */
    public function isNonWorking(\DateTimeInterface $date): bool
    {
        return $this->getDay($date) instanceof NonWorkingDay;
    }

    /**
     * @throws CalendarException
     * @throws ClientExceptionInterface
     * @throws \DateMalformedPeriodStringException
     * @throws \Exception
     */
    public function countWorkingDaysForPeriod(
        \DateTimeInterface $begin,
        \DateTimeInterface $end,
        bool $excludeBegin = false,
        bool $excludeEnd = false
    ): int {
        $count = 0;
        $begin = clone $begin;
        $begin->setTime(0, 0);
        $end = clone $end;
        $excludeEnd ? $end->setTime(0, 0) : $end->setTime(23, 59, 59);

        if ($begin >= $end) {
            throw new CalendarException('Invalid time period');
        }

        $monthBegin = $this->getMonth($begin);

        if (!$monthBegin) {
            throw new CalendarException('Month not found in production calendar by begin date ' . $begin->format('Y-m-d'));
        }

        $monthEnd = $this->getMonth($end);

        if (!$monthEnd) {
            throw new CalendarException('Month not found in production calendar by end date ' . $end->format('Y-m-d'));
        }

        $beginM = clone $begin;
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);

        foreach ($periodM as $dateM) {
            $month = $this->getMonth($dateM);

            if (!$month) {
                throw new CalendarException('Month not found in production calendar by date ' . $dateM->format('Y-m-d'));
            }

            if ($month->getNumberY() == $monthBegin->getNumberY() &&
                $month->getNumberM() == $monthBegin->getNumberM()
            ) {
                if ($monthBegin->getNumberY() == $monthEnd->getNumberY() &&
                    $monthBegin->getNumberM() == $monthEnd->getNumberM()
                ) {
                    $endD = $end;
                } else {
                    $endD = clone $dateM;
                    $endD->modify('last day of this month')->setTime(23, 59, 59);
                }

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD, (int)$excludeBegin);

                foreach ($periodD as $dateD) {
                    if (!$month->getNonWorkingDay((int) $dateD->format('d'))) {
                        $count++;
                    }
                }
            } elseif ($month->getNumberY() == $monthEnd->getNumberY() &&
                $month->getNumberM() == $monthEnd->getNumberM()
            ) {
                $beginD = clone $dateM;
                $beginD->modify('first day of this month');
                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($beginD, $intervalD, $end);

                foreach ($periodD as $dateD) {
                    if (!$month->getNonWorkingDay($dateD->format('d'))) {
                        $count++;
                    }
                }
            } else {
                $count += $month->countWorkingDays();
            }
        }

        return $count;
    }

    /**
     * @throws CalendarException
     * @throws ClientExceptionInterface
     * @throws \DateMalformedPeriodStringException
     */
    public function countNonWorkingDaysForPeriod(
        \DateTimeInterface $begin,
        \DateTimeInterface $end,
        bool $excludeBegin = false,
        bool $excludeEnd = false
    ): int {
        $count = 0;
        $begin = (clone $begin)->setTime(0, 0);
        $end = clone $end;
        $excludeEnd ? $end->setTime(0, 0) : $end->setTime(23, 59, 59);

        if ($begin >= $end) {
            throw new CalendarException('Invalid time period');
        }

        $monthBegin = $this->getMonth($begin);

        if (!$monthBegin) {
            throw new CalendarException('Month not found in production calendar by begin date ' . $begin->format('Y-m-d'));
        }

        $monthEnd = $this->getMonth($end);

        if (!$monthEnd) {
            throw new CalendarException('Month not found in production calendar by end date ' . $end->format('Y-m-d'));
        }

        $beginM = clone $begin;
        $beginM->modify('first day of this month');
        $intervalM = \DateInterval::createFromDateString('1 month');
        $periodM = new \DatePeriod($beginM, $intervalM, $end);

        foreach ($periodM as $dateM) {
            $month = $this->getMonth($dateM);

            if (!$month) {
                throw new CalendarException('Month not found in production calendar by begin date ' . $dateM->format('Y-m-d'));
            }

            if ($month->getNumberY() == $monthBegin->getNumberY() &&
                $month->getNumberM() == $monthBegin->getNumberM()
            ) {
                if ($monthBegin->getNumberY() == $monthEnd->getNumberY() &&
                    $monthBegin->getNumberM() == $monthEnd->getNumberM()
                ) {
                    $endD = $end;
                } else {
                    $endD = clone $dateM;
                    $endD->modify('last day of this month')->setTime(23, 59, 59);
                }

                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($begin, $intervalD, $endD, (int)$excludeBegin);

                foreach ($periodD as $dateD) {
                    if ($month->getNonWorkingDay((int) $dateD->format('d'))) {
                        $count++;
                    }
                }
            } elseif ($month->getNumberY() == $monthEnd->getNumberY() &&
                $month->getNumberM() == $monthEnd->getNumberM()
            ) {
                $beginD = clone $dateM;
                $beginD->modify('first day of this month');
                $intervalD = \DateInterval::createFromDateString('1 day');
                $periodD = new \DatePeriod($beginD, $intervalD, $end);

                foreach ($periodD as $dateD) {
                    if ($month->getNonWorkingDay((int) $dateD->format('d'))) {
                        $count++;
                    }
                }
            } else {
                $count += \count($month->getNonWorkingDays());
            }
        }

        return $count;
    }

    /**
     * Find the nearest working date (including today's)
     * @throws ClientExceptionInterface
     */
    public function nearestWorkingDate(?\DateTimeInterface $date = null): \DateTimeInterface
    {
        $date = $date ?? new \DateTime();

        while ($this->isNonWorking($date)) {
            $interval = \DateInterval::createFromDateString('+1 day');
            $date->add($interval);
        }

        return $date;
    }

    public function clearCacheableYears(): void
    {
        $this->years = [];
    }
}
