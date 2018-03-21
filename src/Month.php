<?php

namespace Devmakis\ProdCalendar;

/**
 * Class Month - месяц производственного календаря
 * @package Devmakis\ProdCalendar
 */
class Month
{
    /**
     * @var int номер месяца
     */
    protected $number;

    /**
     * @var NonWorkingDay[]|array не рабочие дни в месяце
     */
    protected $nonWorkingDays = [];

    /**
     * @var PreHolidayDay[]|array предпраздничные дни
     */
    protected $preHolidayDays = [];

    /**
     * Month constructor.
     * @param int $number
     * @param array $days
     */
    public function __construct($number, array $days)
    {
        $this->number = $number;

        foreach ($days as $day) {
            if (strpos($day, Client::API_LABEL_PRE_HOLIDAY) !== false) {
                $day = (int)str_replace(Client::API_LABEL_PRE_HOLIDAY, '', $day);
                $preHolidayDay = new PreHolidayDay($day);
                $preHolidayDay->setDescription('Предпраздничный день');
                $this->preHolidayDays[$day] = $preHolidayDay;

                continue;
            }

            $nonWorkingDay = new NonWorkingDay($day);
            $nonWorkingDay->setDescription('Не рабочий день');
            $this->nonWorkingDays[$day] = $nonWorkingDay;
        }

    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return NonWorkingDay[]
     */
    public function getNonWorkingDays()
    {
        return $this->nonWorkingDays;
    }

    /**
     * @return PreHolidayDay[]
     */
    public function getPreHolidayDays()
    {
        return $this->preHolidayDays;
    }
}
