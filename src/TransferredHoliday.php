<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar;

class TransferredHoliday extends Day implements NonWorkingDay
{
    protected ?string $description = 'Выходной день (перенесенный праздник)';
}
