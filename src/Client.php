<?php
/**
 * Клиент для получения данных производственных календарей (от сервиса открытых данных России Data.gov.ru)
 */

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Exceptions\ClientException;

class Client
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
    public function requestData()
    {
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

        return $response;
    }
}
