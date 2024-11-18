<?php

namespace Devmakis\ProdCalendar\Cache;

use Devmakis\ProdCalendar\Cache\Exception\CacheException;
use Devmakis\ProdCalendar\Cache\Exception\CacheExpiredException;

/**
 * @deprecated
 */
class FileJsonCache implements ICachable
{
    /**
     * @var string
     */
    protected $file;
    /**
     * @var int|null
     */
    private $expired;
    /**
     * @var array|null
     */
    private $data;

    /**
     * @param string $file
     * @param int|null $expired
     */
    public function __construct($file, $expired = null)
    {
        $this->file = $file;
        $this->expired = $expired;
    }

    /**
     * @param array $data
     * @return void
     * @throws CacheException
     */
    public function write($data)
    {
        if ($this->expired) {
            $data['expired'] = time() + $this->expired;
        }

        $json = (string)\json_encode($data);
        $contents = file_put_contents($this->file, $json);

        if ($contents === false) {
            $error = error_get_last();
            throw new CacheException($error['message']);
        } elseif ($contents === 0) {
            throw new CacheException('file_put_contents: number of bytes written to the file = 0');
        }

        $this->data = $data;
    }

    /**
     * @return array
     * @throws CacheException
     */
    public function read()
    {
        if (!file_exists($this->file)) {
            throw new CacheException('File is not exists');
        }

        $result = file_get_contents($this->file);

        if ($result === false) {
            $error = error_get_last();
            throw new CacheException($error['message']);
        }

        $this->data = \json_decode($result, true) ?: [];

        if (isset($this->data['expired']) && time() > $this->data['expired']) {
            throw new CacheExpiredException('File cache expired');
        }

        return $this->data;
    }

    /**
     * @return array|null
     * @throws CacheException
     */
    public function extend()
    {
        if ($this->data === null) {
            $this->read();
        }

        $this->write($this->data);
        return $this->data;
    }
}
