<?php
/**
 * Created by PhpStorm.
 * User: MAKis
 * Date: 20.03.2018
 * Time: 8:31
 */

namespace Devmakis\ProdCalendar;

use Devmakis\ProdCalendar\Exceptions\CalendarException;

class Calendar
{
    /**
     * @var int номер года
     */

    protected $year;

    /**
     * @var Month[]|array массив объектов месяцев
     */
    protected $months = [];

    /**
     * @var int всего рабочих дней в году
     */
    protected $totalWorkingDays;

    /**
     * @var int всего праздничных и выходных дней в году
     */
    protected $totalNonworkingDays;

    /**
     * @var int количество рабочих часов при 40-часовой рабочей неделе
     */
    protected $numWorkingHours40;

    /**
     * @var int количество рабочих часов при 36-часовой рабочей неделе
     */
    protected $numWorkingHours36;

    /**
     * @var int количество рабочих часов при 24-часовой рабочей неделе
     */
    protected $numWorkingHours24;

    /**
     * Calendar constructor.
     * @param Client $client - клиент сервиса, который предоставляет данные
     * @param int $year - номер года
     * @throws Exceptions\ClientException
     * @throws CalendarException
     */
    public function __construct(Client $client, $year)
    {
        $year = (int)$year;
        $response = $client->requestData();

        foreach ($response as $row) {
            $responseYear = (int)$row[Client::API_DATA_KEYS['YEAR']];

            if ($responseYear !== $year) {
                continue;
            }

            $this->year = $responseYear;
            $this->totalWorkingDays = (int)$row[Client::API_DATA_KEYS['TOTAL_WORKING_DAYS']];
            $this->totalNonworkingDays = (int)$row[Client::API_DATA_KEYS['TOTAL_NONWORKING_DAYS']];
            $this->numWorkingHours40 = (int)$row[Client::API_DATA_KEYS['NUM_WORKING_HOURS_40']];
            $this->numWorkingHours36 = (int)$row[Client::API_DATA_KEYS['NUM_WORKING_HOURS_36']];
            $this->numWorkingHours24 = (int)$row[Client::API_DATA_KEYS['NUM_WORKING_HOURS_24']];

            foreach (Client::API_DATA_KEYS['MONTHS'] as $numberMonth => $keyMonth) {
                $days = explode(Client::API_DELIMITER_DAYS, $row[$keyMonth]);
                $this->months[$numberMonth] = new Month($numberMonth, $days);
            }

            return;
        }

        throw new CalendarException("Production calendar is not found for {$this->year} year");
    }

    /**
     * @return int
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * @return Month[]
     */
    public function getMonths()
    {
        return $this->months;
    }

    /**
     * @return int
     */
    public function getTotalWorkingDays()
    {
        return $this->totalWorkingDays;
    }

    /**
     * @return int
     */
    public function getTotalNonworkingDays()
    {
        return $this->totalNonworkingDays;
    }

    /**
     * @return int
     */
    public function getNumWorkingHours40()
    {
        return $this->numWorkingHours40;
    }

    /**
     * @return int
     */
    public function getNumWorkingHours36()
    {
        return $this->numWorkingHours36;
    }

    /**
     * @return int
     */
    public function getNumWorkingHours24()
    {
        return $this->numWorkingHours24;
    }
}
