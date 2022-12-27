<?php

namespace Devmakis\ProdCalendar\Clients;

/**
 * Interface IClientCacheFile интерфейс для реализации кэша в клиенте
 * @package Devmakis\ProdCalendar\Clients
 * @deprecated
 */
interface ICachedClient
{
    /**
     * Записать в кэш
     */
    public function writeCache();

    /**
     * Прочитать из кэша
     * @return string
     */
    public function readCache();
}
