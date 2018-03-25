<?php

namespace Devmakis\ProdCalendar;

/**
 * Class Day - день производственного календаря
 * @package Devmakis\ProdCalendar
 */
class Day
{
    /**
     * Название дней недели
     */
    const NAME_DAYS_RU = [
        1 => 'Понедельник',
        2 => 'Вторник',
        3 => 'Среда',
        4 => 'Четверг',
        5 => 'Пятница',
        6 => 'Суббота',
        7 => 'Воскресенье',
    ];

    /**
     * @var string номер дня
     */
    protected $numberD;

    /**
     * @var string номер месяца
     */
    protected $numberM;

    /**
     * @var int номер года
     */
    protected $numberY;

    /**
     * @var string описание дня
     */
    protected $description;

    /**
     * Day constructor.
     * @param string $numberD
     * @param $numberM
     * @param $numberY
     */
    public function __construct($numberD, $numberM, $numberY)
    {
        if (strlen($numberD) == 1) {
            $numberD = '0' . $numberD;
        }

        if (strlen($numberM) == 1) {
            $numberM = '0' . $numberM;
        }

        $this->numberD = (string)$numberD;
        $this->numberM = (string)$numberM;
        $this->numberY = (string)$numberY;
    }

    /**
     * @return string
     */
    public function getNumberD()
    {
        return $this->numberD;
    }

    /**
     * @return string
     */
    public function getNumberM()
    {
        return $this->numberM;
    }

    /**
     * @return int
     */
    public function getNumberY()
    {
        return $this->numberY;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Получить объект DateTime
     * @return \DateTime
     */
    public function getDateTime()
    {
        return new \DateTime("{$this->numberD}-{$this->numberM}-{$this->numberY}");
    }
}
