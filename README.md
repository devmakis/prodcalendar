## Описание

Библиотека для работы с производственным календарем.
Она работает с официальными данными от сервис открытых данных России http://data.gov.ru/. 
Так же имеется возможность реализовать свой клиент для получения данных от других сервисов.

## Установка

`composer require devmakis/prodcalendar`

## Пример использования

`use Devmakis\ProdCalendar\Clients\DataGovClient;`
`use Devmakis\ProdCalendar\Calendar;`

`$client = new DataGovClient('YOUR_TOKEN');`
`$calendar = new Calendar($client);`

Проверяем является ли день нерабочим (выходным | праздничным)

`$calendar->isNonWorking(new DateTime('01-01-2018'));`
`$calendar->isWeekend(new DateTime('01-01-2018'));`
`$calendar->isHoliday(new DateTime('01-01-2018'));`

Проверяем является ли день предпраздничным

`$calendar->isPreHoliday(new DateTime('22-02-2018'));`

Получаем количество рабочих | нерабочих дней за определенный период

`$calendar->countWorkingDaysForPeriod(new DateTime('31-01-2018'), new DateTime('08-05-2018'));`
`$calendar->countNonWorkingDaysForPeriod(new DateTime('31-01-2018'), new DateTime('08-05-2018'));`

Получаем производственный календарь за определенный год, узнаем количество рабочих | нерабочих дней в году, в месяце

`$year2018 = $calendar->getYear('2018');`
`$countNonWorkingDays = $year2018->countNonWorkingDays();`
`$countWorkingDays = $year2018->countWorkingDays();`
`$countWorkingDaysInMay = $year2018->getMonth('05')->countWorkingDays();`
