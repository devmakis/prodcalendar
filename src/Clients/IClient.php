<?php

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Year;

/**
 * Interface IClient интерфейс для реализации клиента
 * @package Devmakis\ProdCalendar
 */
interface IClient
{
    /**
     * Получить произвоственный календарь за определенный год
     * @param string $numberY номер года
     * @return Year
     */
    public function getYear($numberY);
}
