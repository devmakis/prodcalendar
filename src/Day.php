<?php

namespace Devmakis\ProdCalendar;

/**
 * Class Day - день производственного календаря
 * @package Devmakis\ProdCalendar
 */
class Day
{
    /**
     * @var int номер месяца
     */
    protected $number;

    /**
     * @var string описание дня
     */
    protected $description;

    /**
     * Day constructor.
     * @param int $number
     */
    public function __construct($number)
    {
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
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
}
