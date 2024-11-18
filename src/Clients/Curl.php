<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Clients\Exceptions\ClientCurlException;

/** @deprecated */
class Curl
{
    public const array DEFAULT_CURL_OPTIONS = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR    => true,
        CURLOPT_FOLLOWLOCATION => true,
    ];

    public function __construct(
        protected array $options = []
    ) {
        $this->options = \array_replace(self::DEFAULT_CURL_OPTIONS, $this->options);
    }

    /**
     * @throws ClientCurlException
     */
    public function request($url): string
    {
        $curl = \curl_init($url);
        \curl_setopt_array($curl, $this->options);
        $response = \curl_exec($curl);

        if ($response === false) {
            $errorCode = \curl_errno($curl);
            $errorMessage = \curl_error($curl);
            \curl_close($curl);

            throw new ClientCurlException('cURL request get error - ' . $errorMessage, $errorCode);
        }

        \curl_close($curl);

        return $response;
    }
}
