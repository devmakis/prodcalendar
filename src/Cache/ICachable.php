<?php

namespace Devmakis\ProdCalendar\Cache;

use Devmakis\ProdCalendar\Cache\Exception\CacheException;

/**
 * Interface IClientCacheFile интерфейс для реализации кэша в клиенте
 * @package Devmakis\ProdCalendar\Clients
 */
interface ICachable
{
    /**
     * Записать в кэш
     * @return void
     * @throws CacheException
     */
    public function write($data);

    /**
     * Прочитать из кэша
     * @return mixed
     * @throws CacheException
     */
    public function read();

    /**
     * Продлить срок жизни кэша
     * @return mixed
     */
    public function extend();
}
