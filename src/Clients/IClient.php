<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Year;

interface IClient
{
    /**
     * @throws ClientExceptionInterface
     */
    public function getYear(int $yearNumber): Year;
}
