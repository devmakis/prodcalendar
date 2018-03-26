<?php

namespace Devmakis\ProdCalendar\Clients;

/**
 * Interface IClientCacheFile интерфейс для реализации файлового кэша в клиенте
 * @package Devmakis\ProdCalendar\Clients
 */
interface IClientCacheFile
{
    /**
     * Возвращает путь до файла
     * @return string
     */
    public function getCacheFile();

    /**
     * Установить путь до файла
     * @param $cacheFile
     */
    public function setCacheFile($cacheFile);

    /**
     * Сохранить данные в файл
     * @param mixed $data записываемые данные
     * return bool
     */
    public function cacheToFile($data);

    /**
     * Прочитать данные из файла
     * @return string
     */
    public function readFromFile();
}
