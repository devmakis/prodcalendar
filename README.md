## Описание

Библиотека для работы с производственным календарем.
Она работает с официальными данными от сервиса открытых данных России http://data.gov.ru/ 
(необходимо зарегистрироваться на данном ресурсе и получить токен). 
Так же имеется возможность реализовать свой клиент для получения данных от других источников.

## Установка

`composer require devmakis/prodcalendar`

## Пример использования

```php
use Devmakis\ProdCalendar\Clients\DataGovClient;
use Devmakis\ProdCalendar\Calendar;

$client = new DataGovClient('YOUR_TOKEN');
$calendar = new Calendar($client);
```

Проверяем является ли день нерабочим (выходным | праздничным)

```php
$calendar->isNonWorking(new DateTime('01-01-2018'));
$calendar->isWeekend(new DateTime('01-01-2018'));
$calendar->isHoliday(new DateTime('01-01-2018'));
```

Проверяем является ли день предпраздничным

```php
$calendar->isPreHoliday(new DateTime('22-02-2018'));
```

Получаем количество рабочих | нерабочих дней за определенный период

```php
$countWorkingDays = $calendar->countWorkingDaysForPeriod(new DateTime('31-01-2018'), new DateTime('08-05-2018'));
$countNonWorkingDays = $calendar->countNonWorkingDaysForPeriod(new DateTime('31-01-2018'), new DateTime('08-05-2018'));
```

Получаем производственный календарь за определенный год, узнаем количество рабочих | нерабочих дней в году, в месяце

```php
$year2018 = $calendar->getYear('2018');
$countWorkingDays = $year2018->countWorkingDays();
$countNonWorkingDays = $year2018->countNonWorkingDays();
$countWorkingDaysInMay = $year2018->getMonth('05')->countWorkingDays();
```
