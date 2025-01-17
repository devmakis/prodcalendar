## Описание

Библиотека для работы с производственным календарем России, Белоруссии, Казахстана и Узбекистана на основе [xmlcalendar.ru](http://xmlcalendar.ru). 
Есть возможность реализовать свой клиент для получения данных от других источников.

## Установка

`composer require devmakis/prodcalendar`

## Примеры использования

```php
use Devmakis\ProdCalendar\Cache\FileJsonCache;
use Devmakis\ProdCalendar\Clients\XmlCalendarClient;
use Devmakis\ProdCalendar\Calendar;
use Devmakis\ProdCalendar\Country;

$cache = new FileJsonCache('FILE_PATH', 3600);
$client = new XmlCalendarClient(Country::RUSSIA, $cache);
$calendar = new Calendar($client);
```

Проверяем является ли день нерабочим (выходным | праздничным | перенесенным праздником)

```php
$calendar->isNonWorking(new DateTime('01-01-2018'));
$calendar->isWeekend(new DateTime('01-01-2018'));
$calendar->isHoliday(new DateTime('01-01-2018'));
$calendar->isTransferredHoliday(new DateTime('24-03-2020'));
```

Проверяем является ли день предпраздничным

```php
$calendar->isPreHoliday(new DateTime('22-02-2018'));
```

Получаем количество рабочих | нерабочих дней за определенный период

```php
$dateBegin = new DateTime('31-01-2018');
$dateEnd = new DateTime('08-05-2018');
$countWorkingDays = $calendar->countWorkingDaysForPeriod($dateBegin, $dateEnd);
$countNonWorkingDays = $calendar->countNonWorkingDaysForPeriod($dateBegin, $dateEnd);
```

Получаем производственный календарь за определенный год, узнаем количество рабочих | нерабочих дней в году, в месяце

```php
$year2018 = $calendar->getYear('2018');
$countWorkingDays = $year2018->countWorkingDays();
$countNonWorkingDays = $year2018->countNonWorkingDays();
$countWorkingDaysInMay = $year2018->getMonth('05')->countWorkingDays();
```
