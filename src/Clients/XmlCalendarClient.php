<?php
/**
 * Клиент для получения данных производственных календарей (от http://xmlcalendar.ru)
 */

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Cache\Exception\CacheException;
use Devmakis\ProdCalendar\Cache\ICachable;
use Devmakis\ProdCalendar\Clients\Exceptions\ClientCurlException;
use Devmakis\ProdCalendar\Clients\Exceptions\ClientException;
use Devmakis\ProdCalendar\Day;
use Devmakis\ProdCalendar\Holiday;
use Devmakis\ProdCalendar\Holidays;
use Devmakis\ProdCalendar\Month;
use Devmakis\ProdCalendar\PreHolidayDay;
use Devmakis\ProdCalendar\TransferredHoliday;
use Devmakis\ProdCalendar\Weekend;
use Devmakis\ProdCalendar\Year;

class XmlCalendarClient implements IClient
{
    use Holidays;

    /**
     * Разделитель дней в данных API сервиса
     */
    const API_DELIMITER_DAYS = ',';

    /**
     * Метка предпраздничного дня в данных API сервиса
     */
    const API_LABEL_PRE_HOLIDAY = '*';

    /**
     * Метка перенесенного праздника в данных API сервиса
     */
    const API_LABEL_TRANSFERRED_HOLIDAY = '+';

    /**
     * @var array
     */
    protected $data = [];
    /**
     * @var string
     */
    private $country;
    /**
     * @var ICachable|null
     */
    protected $cache;
    /**
     * @var Curl|null
     */
    private $curl;

    public function __construct($country, ICachable $cache = null, Curl $curl = null)
    {
        $this->country = $country;
        $this->cache = $cache;
        $this->curl = $curl ?: new Curl();
    }

    /**
     * @param $numberY
     * @return Year
     * @throws ClientException|CacheException
     * @throws \Exception
     */
    public function getYear($numberY)
    {
        if (empty($this->data)) {
            try {
                $this->data = $this->cache->read();
            } catch (CacheException $e) {
            }

            if (!isset($this->data[$this->country][$numberY])) {
                try {
                    $contents = $this->curl->request(sprintf(
                        'http://xmlcalendar.ru/data/%s/%s/calendar.json',
                        $this->country,
                        $numberY
                    ));
                    $this->data[$this->country][$numberY] = \json_decode($contents, true);
                    $this->cache->write($this->data);
                } catch (ClientCurlException $e) {
                    $this->data = $this->cache->extend();
                }
            }
        }

        if (!isset($this->data[$this->country][$numberY])) {
            throw new ClientException('Year not found');
        }

        $months = [];

        foreach ($this->data[$this->country][$numberY]['months'] as $monthData) {
            $numberM = $monthData['month'];
            $days = explode(self::API_DELIMITER_DAYS, $monthData['days']);
            $nonWorkingDays = [];
            $preHolidayDays = [];

            foreach ($days as $numberD) {
                // Определение предпраздничного дня по метке от АПИ
                if (strpos($numberD, self::API_LABEL_PRE_HOLIDAY) !== false) {
                    $numberD = str_replace(self::API_LABEL_PRE_HOLIDAY, '', $numberD);
                    $preHolidayDay = new PreHolidayDay($numberD, $numberM, $numberY);
                    $preHolidayDays[$preHolidayDay->getNumberD()] = $preHolidayDay;

                    continue;
                }

                // Определение перенесенного праздника по метке от АПИ
                if (strpos($numberD, self::API_LABEL_TRANSFERRED_HOLIDAY) !== false) {
                    $numberD = str_replace(self::API_LABEL_TRANSFERRED_HOLIDAY, '', $numberD);
                    $nonWorkingDay = new TransferredHoliday($numberD, $numberM, $numberY);
                    $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                    continue;
                }

                // Определение праздничного
                $nonWorkingDay = new Day($numberD, $numberM, $numberY);
                $keyHoliday = $nonWorkingDay->getNumberD() . '.' . $nonWorkingDay->getNumberM();
                $nonworkingHolidays = $this->getNonworkingHolidays();

                if (array_key_exists($keyHoliday, $nonworkingHolidays)) {
                    $nonWorkingDay = new Holiday($numberD, $numberM, $numberY);
                    $nonWorkingDay->setDescription($nonworkingHolidays[$keyHoliday]);
                    $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                    continue;
                }

                // Определение перенесенного праздника, если нет меки от АПИ (это не сб и не вск)
                $nDayWeek = $nonWorkingDay->getDateTime()->format('N');

                if (!in_array($nDayWeek, [6, 7])) {
                    $nonWorkingDay = new TransferredHoliday($numberD, $numberM, $numberY);
                    $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                    continue;
                }

                // Если не все что выше, значит это обычный выходной день
                $nonWorkingDay = new Weekend($numberD, $numberM, $numberY);
                $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;
            }

            $month = new Month($numberM, $numberY, $nonWorkingDays, $preHolidayDays);
            $months[$month->getNumberM()] = $month;
        }

        $calendar = new Year($numberY, $months);
        $calendar->setNumWorkingHours40($this->data[$this->country][$numberY]['statistic']['hours40']);
        $calendar->setNumWorkingHours36($this->data[$this->country][$numberY]['statistic']['hours36']);
        $calendar->setNumWorkingHours24($this->data[$this->country][$numberY]['statistic']['hours24']);

        return $calendar;
    }
}
