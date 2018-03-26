<?php

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Clients\Exceptions\ClientException;
use Devmakis\ProdCalendar\Year;

/**
 * Interface IClient интерфейс для реализации клиента
 * @package Devmakis\ProdCalendar
 */
interface IClient
{
    /**
     * Запросит данные у API сервиса
     * @return mixed
     * @throws ClientException
     */
    public function request();

    /**
     * Получить произвоственный календарь за определенный год
     * @param string $numberY номер года
     * @return Year
     * @throws ClientException
     */
    public function getYear($numberY);
}
