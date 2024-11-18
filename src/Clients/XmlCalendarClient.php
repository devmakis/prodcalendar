<?php

declare(strict_types=1);

namespace Devmakis\ProdCalendar\Clients;

use Devmakis\ProdCalendar\Clients\Exceptions\ClientException;
use Devmakis\ProdCalendar\Day;
use Devmakis\ProdCalendar\Holiday;
use Devmakis\ProdCalendar\Holidays;
use Devmakis\ProdCalendar\Month;
use Devmakis\ProdCalendar\PreHolidayDay;
use Devmakis\ProdCalendar\TransferredHoliday;
use Devmakis\ProdCalendar\Weekend;
use Devmakis\ProdCalendar\Year;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class XmlCalendarClient implements IClient
{
    use Holidays;

    protected const string BASE_URI = 'https://xmlcalendar.github.io';

    public const string API_DELIMITER_DAYS = ',';

    public const string API_LABEL_PRE_HOLIDAY = '*';

    public const string API_LABEL_TRANSFERRED_HOLIDAY = '+';

    protected array $data = [];

    protected null|int|\DateInterval $cacheTtl = null;

    public function __construct(
        protected string $country,
        protected ClientInterface $httpClient,
        protected ?CacheInterface $cache = null,
    ) {}

    public function setCacheTtl(null|int|\DateInterval $cacheTtl): void
    {
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * @throws InvalidArgumentException
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function getYear(int $yearNumber): Year
    {
        if (!isset($this->data[$yearNumber])) {
            if ($this->cache) {
                $this->data[$yearNumber] = $this->cache->get((string) $yearNumber);
            }

            if (!isset($this->data[$yearNumber])) {
                $uri = \sprintf('%s/data/%s/%d/calendar.json', self::BASE_URI, $this->country, $yearNumber);
                $request = new Request('GET', $uri);
                $response = $this->httpClient->sendRequest($request);
                $this->data[$yearNumber] = \json_decode($response->getBody()->getContents(), true);
                $this->cache?->set((string) $yearNumber, $this->data[$yearNumber], $this->cacheTtl);
            }
        }

        if (!isset($this->data[$yearNumber])) {
            throw new ClientException($yearNumber . ' year not found');
        }

        $months = [];

        foreach ($this->data[$yearNumber]['months'] as $monthData) {
            $monthNumber = (int) $monthData['month'];
            $days = \explode(self::API_DELIMITER_DAYS, $monthData['days'] ?? []);
            $nonWorkingDays = [];
            $preHolidayDays = [];

            foreach ($days as $dayNumber) {
                if (\str_contains($dayNumber, self::API_LABEL_PRE_HOLIDAY)) {
                    $dayNumber = \str_replace(self::API_LABEL_PRE_HOLIDAY, '', $dayNumber);
                    $preHolidayDay = new PreHolidayDay((int) $dayNumber, $monthNumber, $yearNumber);
                    $preHolidayDays[$preHolidayDay->getNumberD()] = $preHolidayDay;

                    continue;
                }

                if (\str_contains($dayNumber, self::API_LABEL_TRANSFERRED_HOLIDAY)) {
                    $dayNumber = \str_replace(self::API_LABEL_TRANSFERRED_HOLIDAY, '', $dayNumber);
                    $nonWorkingDay = new TransferredHoliday((int) $dayNumber, $monthNumber, $yearNumber);
                    $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                    continue;
                }

                $nonWorkingDay = new Day((int) $dayNumber, $monthNumber, $yearNumber);
                $keyHoliday = $nonWorkingDay->getNumberD() . '.' . $nonWorkingDay->getNumberM();
                $nonworkingHolidays = $this->getNonworkingHolidays();

                if (\array_key_exists($keyHoliday, $nonworkingHolidays)) {
                    $nonWorkingDay = new Holiday((int) $dayNumber, $monthNumber, $yearNumber);
                    $nonWorkingDay->setDescription($nonworkingHolidays[$keyHoliday]);
                    $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                    continue;
                }

                $dayWeekNumber = (new \DateTime((string) $nonWorkingDay))->format('N');

                if (!\in_array($dayWeekNumber, [6, 7])) {
                    $nonWorkingDay = new TransferredHoliday((int) $dayNumber, $monthNumber, $yearNumber);
                    $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;

                    continue;
                }

                $nonWorkingDay = new Weekend((int) $dayNumber, $monthNumber, $yearNumber);
                $nonWorkingDays[$nonWorkingDay->getNumberD()] = $nonWorkingDay;
            }

            $month = new Month($monthNumber, $yearNumber, $nonWorkingDays, $preHolidayDays);
            $months[$month->getNumberM()] = $month;
        }

        $year = new Year($yearNumber, $months);

        if (isset($this->data[$yearNumber]['statistic']['hours40'])) {
            $year->setNumberWorkingHours40((float) $this->data[$yearNumber]['statistic']['hours40']);
        }

        if (isset($this->data[$yearNumber]['statistic']['hours36'])) {
            $year->setNumberWorkingHours36((float) $this->data[$yearNumber]['statistic']['hours36']);
        }

        if (isset($this->data[$yearNumber]['statistic']['hours24'])) {
            $year->setNumberWorkingHours24((float) $this->data[$yearNumber]['statistic']['hours24']);
        }

        return $year;
    }
}
