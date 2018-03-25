<?php
/**
 * Клиент для получения данных производственных календарей (от сервиса открытых данных России Data.gov.ru)
 */

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Day;
use Devmakis\ProdCalendar\Exceptions\CalendarException;
use Devmakis\ProdCalendar\Holiday;
use Devmakis\ProdCalendar\Month;
use Devmakis\ProdCalendar\PreHolidayDay;
use Devmakis\ProdCalendar\Weekend;
use Devmakis\ProdCalendar\Year;

class DataGovClient implements IClient
{
    /**
     * Корневой адрес запроса к API сервиса
     */
    const ROOT_URL = 'http://data.gov.ru/api/json/dataset/7708660670-proizvcalendar/version/20151123T183036/content/';

    /**
     * Ключи данных API сервиса
     */
    const API_DATA_KEYS = [
        'YEAR'                  => 'Год/Месяц',
        'MONTHS'                => [
            1  => 'Январь',
            2  => 'Февраль',
            3  => 'Март',
            4  => 'Апрель',
            5  => 'Май',
            6  => 'Июнь',
            7  => 'Июль',
            8  => 'Август',
            9  => 'Сентябрь',
            10 => 'Октябрь',
            11 => 'Ноябрь',
            12 => 'Декабрь',
        ],
        'TOTAL_WORKING_DAYS'    => 'Всего рабочих дней',
        'TOTAL_NONWORKING_DAYS' => 'Всего праздничных и выходных дней',
        'NUM_WORKING_HOURS_40'  => 'Количество рабочих часов при 40-часовой рабочей неделе',
        'NUM_WORKING_HOURS_36'  => 'Количество рабочих часов при 36-часовой рабочей неделе',
        'NUM_WORKING_HOURS_24'  => 'Количество рабочих часов при 24-часовой рабочей неделе',
    ];

    /**
     * Разделитель дней в данных API сервиса
     */
    const API_DELIMITER_DAYS = ',';

    /**
     * Метка предпраздничного дня в данных API сервиса
     */
    const API_LABEL_PRE_HOLIDAY = '*';

    /**
     * Нерабочие праздничные дни
     * согласно Статье 112 "Трудовой кодекс Российской Федерации" от 30.12.2001 N 197-ФЗ (ред. от 05.02.2018)
     * @link https://www.consultant.ru/document/cons_doc_LAW_34683/98ef2900507766e70ff29c0b9d8e2353ea80a1cf/#dst102376
     */
    const NONWORKING_HOLIDAYS = [
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

    /**
     * @var string ключ для работы с API сервиса
     */
    protected $token;

    /**
     * @var string полный адрес запроса к API
     */
    protected $requestUrl;

    /**
     * @var int количество секунд ожидания при попытке соединения
     */
    protected $timeout;

    /**
     * @var int максимально позволенное количество секунд для выполнения cURL-функций
     */
    protected $connectTimeout;

    /**
     * Данные ответа от API сервиса
     * @var string
     */
    protected $response;


    /**
     * Client constructor.
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
        $this->requestUrl = self::ROOT_URL . '?access_token=' . $this->token;
        $this->timeout = 1;
        $this->connectTimeout = 1;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    /**
     * @param int $connectTimeout
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * Запросит данные у API сервиса
     * @throws ClientException
     * return array $response
     */
    public function request()
    {
        if ($this->response) {
            return $this->response;
        }

        $curl = curl_init($this->requestUrl);
        curl_setopt_array($curl, [
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR    => true,
        ]);
        $response = curl_exec($curl);

        if ($response === false) {
            $errorCode = curl_errno($curl);
            $errorMessage = curl_error($curl);
            curl_close($curl);

            throw new ClientException('cURL request get error - ' . $errorMessage, $errorCode);
        }

        curl_close($curl);
        $response = json_decode($response, true);

        if (!$response) {
            throw new ClientException('Response is empty or incorrect');
        }

        $this->response = $response;

        return $this->response;
    }

    /**
     * @param $numberY
     * @return Year
     * @throws CalendarException
     * @throws ClientException
     */
    public function getYear($numberY)
    {
        $numberY = (string)$numberY;
        $response = $this->request();

        foreach ($response as $row) {
            $responseYear = (string)$row[DataGovClient::API_DATA_KEYS['YEAR']];

            if ($responseYear !== $numberY) {
                continue;
            }

            $months = [];

            foreach (DataGovClient::API_DATA_KEYS['MONTHS'] as $numberM => $keyM) {
                $days = explode(DataGovClient::API_DELIMITER_DAYS, $row[$keyM]);
                $nonWorkingDays = [];
                $preHolidayDays = [];

                foreach ($days as $numberD) {
                    if (strpos($numberD, DataGovClient::API_LABEL_PRE_HOLIDAY) !== false) {
                        $numberD = str_replace(DataGovClient::API_LABEL_PRE_HOLIDAY, '', $numberD);
                        $preHolidayDay = new PreHolidayDay($numberD, $numberM, $numberY);
                        $preHolidayDays[$numberD] = $preHolidayDay;

                        continue;
                    }

                    $nonWorkingDay = new Day($numberD, $numberM, $numberY);
                    $keyHoliday = $nonWorkingDay->getNumberD() . '.' . $nonWorkingDay->getNumberM();

                    if (isset(self::NONWORKING_HOLIDAYS[$keyHoliday])) {
                        $nonWorkingDay = new Holiday($numberD, $numberM, $numberY);
                        $nonWorkingDay->setDescription(self::NONWORKING_HOLIDAYS[$keyHoliday]);
                    } else {
                        $nonWorkingDay = new Weekend($numberD, $numberM, $numberY);
                    }

                    $nonWorkingDays[(int)$numberD] = $nonWorkingDay;
                }

                $months[(int)$numberM] = new Month($numberM, $numberY, $nonWorkingDays, $preHolidayDays);
            }

            $calendar = new Year($numberY, $months);
            $calendar->setNumWorkingHours40($row[DataGovClient::API_DATA_KEYS['NUM_WORKING_HOURS_40']]);
            $calendar->setNumWorkingHours36($row[DataGovClient::API_DATA_KEYS['NUM_WORKING_HOURS_36']]);
            $calendar->setNumWorkingHours24($row[DataGovClient::API_DATA_KEYS['NUM_WORKING_HOURS_24']]);

            return $calendar;
        }

        throw new CalendarException("Production calendar is not found for «{$numberY}» year");
    }
}
