<?php

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Clients\Exceptions\ClientCacheException;
use Devmakis\ProdCalendar\Clients\Exceptions\ClientCurlException;
use Devmakis\ProdCalendar\Clients\Exceptions\ClientEmptyResponseException;
use Devmakis\ProdCalendar\Clients\Exceptions\ClientException;
use Devmakis\ProdCalendar\Day;
use Devmakis\ProdCalendar\Holiday;
use Devmakis\ProdCalendar\Holidays;
use Devmakis\ProdCalendar\Month;
use Devmakis\ProdCalendar\PreHolidayDay;
use Devmakis\ProdCalendar\TransferredHoliday;
use Devmakis\ProdCalendar\Weekend;
use Devmakis\ProdCalendar\Year;

/**
 * Client for obtaining production calendar data (from the Russian open data service Data.gov.ru)
 * @deprecated
 */
class DataGovClient implements IClient, ICachedClient
{
    use Holidays;

    public const ROOT_URL = 'https://data.gov.ru/api/json/dataset/7708660670-proizvcalendar/version/20191112T155500/content/';

    public const DEFAULT_CURL_OPTIONS = [
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR    => true,
        CURLOPT_FOLLOWLOCATION => true,
    ];

    public const API_DATA_KEYS = [
        'YEAR' => 'Год/Месяц',
        'MONTHS' => [
            '01' => 'Январь',
            '02' => 'Февраль',
            '03' => 'Март',
            '04' => 'Апрель',
            '05' => 'Май',
            '06' => 'Июнь',
            '07' => 'Июль',
            '08' => 'Август',
            '09' => 'Сентябрь',
            '10' => 'Октябрь',
            '11' => 'Ноябрь',
            '12' => 'Декабрь',
        ],
        'TOTAL_WORKING_DAYS' => 'Всего рабочих дней',
        'TOTAL_NONWORKING_DAYS' => 'Всего праздничных и выходных дней',
        'NUM_WORKING_HOURS_40' => 'Количество рабочих часов при 40-часовой рабочей неделе',
        'NUM_WORKING_HOURS_36' => 'Количество рабочих часов при 36-часовой рабочей неделе',
        'NUM_WORKING_HOURS_24' => 'Количество рабочих часов при 24-часовой рабочей неделе',
    ];

    public const API_DELIMITER_DAYS = ',';

    public const API_LABEL_PRE_HOLIDAY = '*';

    public const API_LABEL_TRANSFERRED_HOLIDAY = '+';

    /**
     * @deprecated
     * @see self::getNonworkingHolidays()
     */
    public const NONWORKING_HOLIDAYS = [
        '01.01' => 'Новогодние каникулы',
        '02.01' => 'Новогодние каникулы',
        '03.01' => 'Новогодние каникулы',
        '04.01' => 'Новогодние каникулы',
        '05.01' => 'Новогодние каникулы',
        '06.01' => 'Новогодние каникулы',
        '07.01' => 'Рождество Христово',
        '08.01' => 'Новогодние каникулы',
        '23.02' => 'День защитника Отечества',
        '08.03' => 'Международный женский день',
        '01.05' => 'Праздник Весны и Труда',
        '09.05' => 'День Победы',
        '12.06' => 'День России',
        '04.11' => 'День народного единства',
    ];

    public const CACHE_EXTEND_TIME = 3600 * 24;

    protected string $requestUrl;

    protected array $data;

    protected string $cacheFile;

    protected int $cacheLifetime = 60 * 60 * 24 * 15;

    public function __construct(
        protected string $token,
        protected array $curlOptions = []
    ) {
        $this->requestUrl = self::ROOT_URL . '?access_token=' . $this->token;
        $this->curlOptions = \array_replace(self::DEFAULT_CURL_OPTIONS, $curlOptions);
    }

    public function setCurlOption($name, $value): void
    {
        $this->curlOptions[$name] = $value;
    }

    public function setTimeout(int $timeout): void
    {
        $this->setCurlOption(CURLOPT_TIMEOUT, $timeout);
    }

    public function setConnectTimeout(int $connectTimeout): void
    {
        $this->setCurlOption(CURLOPT_CONNECTTIMEOUT, $connectTimeout);
    }

    public function getCacheFile(): string
    {
        return $this->cacheFile;
    }

    public function setCacheFile(string $cacheFile): void
    {
        $this->cacheFile = $cacheFile;
    }

    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime;
    }

    public function setCacheLifetime(int $cacheLifetime): void
    {
        $this->cacheLifetime = (int)$cacheLifetime;
    }

    /**
     * @throws ClientCurlException
     */
    public function request(): string
    {
        $curl = \curl_init($this->requestUrl);
        \curl_setopt_array($curl, $this->curlOptions);
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

    /**
     * @throws ClientCurlException
     * @throws ClientException
     */
    public function getData(): array
    {
        if ($this->data) {
            return $this->data;
        }

        try {
            $result = $this->readCache();
        } catch (ClientException $e) {
            $result = $this->request();
        }

        $this->data = \json_decode($result, true);

        if (!$this->data) {
            throw new ClientException('Data is empty or incorrect');
        }

        return $this->data;
    }

    /**
     * @throws ClientException
     * @throws \Exception
     */
    public function getYear(int $yearNumber): Year
    {
        foreach ($this->getData() as $row) {
            $responseYear = (int)$row[DataGovClient::API_DATA_KEYS['YEAR']];

            if ($responseYear != $yearNumber) {
                continue;
            }

            $months = [];

            foreach (DataGovClient::API_DATA_KEYS['MONTHS'] as $monthNumber => $keyM) {
                $monthNumber = (int)$monthNumber;
                $days = \explode(self::API_DELIMITER_DAYS, $row[$keyM]);
                $nonWorkingDays = [];
                $preHolidayDays = [];

                foreach ($days as $dayNumber) {
                    $dayNumber = (int)$dayNumber;

                    if (\str_contains($dayNumber, self::API_LABEL_PRE_HOLIDAY)) {
                        $dayNumber = \str_replace(self::API_LABEL_PRE_HOLIDAY, '', $dayNumber);
                        $preHolidayDay = new PreHolidayDay($dayNumber, $monthNumber, $yearNumber);
                        $preHolidayDays[$preHolidayDay->getNumberD()] = $preHolidayDay;

                        continue;
                    }

                    if (\str_contains($dayNumber, self::API_LABEL_TRANSFERRED_HOLIDAY)) {
                        $dayNumber = \str_replace(self::API_LABEL_TRANSFERRED_HOLIDAY, '', $dayNumber);
                        $nonWorkingDay = new TransferredHoliday($dayNumber, $monthNumber, $yearNumber);
                        $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                        continue;
                    }

                    $nonWorkingDay = new Day($dayNumber, $monthNumber, $yearNumber);
                    $keyHoliday = $nonWorkingDay->getNumberD() . '.' . $nonWorkingDay->getNumberM();
                    $nonworkingHolidays = $this->getNonworkingHolidays();

                    if (\array_key_exists($keyHoliday, $nonworkingHolidays)) {
                        $nonWorkingDay = new Holiday($dayNumber, $monthNumber, $yearNumber);
                        $nonWorkingDay->setDescription($nonworkingHolidays[$keyHoliday]);
                        $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                        continue;
                    }

                    $nDayWeek = (new \DateTime($nonWorkingDay))->format('N');

                    if (!\in_array($nDayWeek, [6, 7])) {
                        $nonWorkingDay = new TransferredHoliday($dayNumber, $monthNumber, $yearNumber);
                        $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                        continue;
                    }

                    $nonWorkingDay = new Weekend($dayNumber, $monthNumber, $yearNumber);
                    $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;
                }

                $month = new Month($monthNumber, $yearNumber, $nonWorkingDays, $preHolidayDays);
                $months[$month->getNumberM()] = $month;
            }

            $calendar = new Year($yearNumber, $months);
            $calendar->setNumWorkingHours40($row[self::API_DATA_KEYS['NUM_WORKING_HOURS_40']]);
            $calendar->setNumWorkingHours36($row[self::API_DATA_KEYS['NUM_WORKING_HOURS_36']]);
            $calendar->setNumWorkingHours24($row[self::API_DATA_KEYS['NUM_WORKING_HOURS_24']]);

            return $calendar;
        }

        throw new ClientException('Production calendar is not found for «' . $yearNumber . '» year');
    }

    /**
     * @throws ClientCacheException
     * @throws ClientCurlException
     * @throws ClientEmptyResponseException
     */
    public function writeCache(): void
    {
        if (!$this->getCacheFile()) {
            throw new ClientCacheException('The path to the cached file is not set');
        }

        $result = $this->request();
        $arrResult = \json_decode($result, true);

        if (empty($arrResult)) {
            throw new ClientEmptyResponseException('Empty response');
        }

        $contents = \file_put_contents($this->getCacheFile(), $result);

        if ($contents === false) {
            $error = \error_get_last();
            throw new ClientCacheException($error['message']);
        } elseif ($contents === 0) {
            throw new ClientCacheException('file_put_contents: number of bytes written to the file = 0');
        }
    }

    /**
     * @throws ClientCacheException
     * @throws ClientCurlException
     * @throws ClientEmptyResponseException
     */
    public function readCache(): string
    {
        if (!$this->getCacheFile()) {
            throw new ClientCacheException('The path to the cached file is not set');
        }

        if (!\file_exists($this->getCacheFile())) {
            $this->writeCache();
        } else {
            $timeLastUpdateFile = \filemtime($this->getCacheFile());

            if ($timeLastUpdateFile < \time() - $this->cacheLifetime) {
                try {
                    $this->writeCache();
                } catch (ClientCurlException $e) {
                    if (\in_array($e->getCode(), [401, 403])) {
                        throw $e;
                    }

                    // We change the modification time of the cache file if an error comes from the server
                    \touch($this->getCacheFile(), time() + self::CACHE_EXTEND_TIME);
                } catch (ClientEmptyResponseException $e) {
                    // Change the modification time of the cache file if an empty response is received from the server
                    \touch($this->getCacheFile(), time() + self::CACHE_EXTEND_TIME);
                }
            }
        }

        $result = \file_get_contents($this->getCacheFile());

        if ($result === false) {
            $error = \error_get_last();
            throw new ClientCacheException($error['message']);
        }

        return $result;
    }
}
