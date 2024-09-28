<?php

namespace Bkucenski\Quickdry\Utilities;

use DateTime;
use DateTimeZone;
use Exception;

/**
 * Class Dates
 */
class Dates
{
    /**
     * @param $min_date
     * @param $max_date
     * @return float|int
     */
    public static function FractionalMonthsBetweenDates($min_date, $max_date): float|int
    {
        $months = 0;
        try {
            $min_date = new DateTime($min_date);
            $max_date = new DateTime($max_date);
            $interval = date_diff($min_date, $max_date);
            $months = ($interval->y * 12) + $interval->m; // Total number of whole months
            $months += round($interval->d / 30, 1); // Add the month fraction
        } catch (Exception $ex) {
            Debug($ex);
        }
        return $months;
    }

    /**
     * @param $start_date
     * @param $end_date
     * @return float
     */
    public static function DaysDiff($start_date, $end_date): float
    {
        $start_date = strtotime(self::Datestamp($start_date));
        $end_date = strtotime(self::Datestamp($end_date));
        return floor(($end_date - $start_date) / 3600.0 / 24.0);
    }

    /**
     * @param $datetime
     * @param $timezone
     * @return string
     * @throws Exception
     */
    public static function ConvertToUserDate($datetime, $timezone): string
    {
        $datetime = Dates::Timestamp($datetime);
        $tz = new DateTimeZone($timezone);
        try {
            $date = new DateTime($datetime . ' GMT');
            $date->setTimezone($tz);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            Debug::Halt($e);
            exit;
        }
    }

    /**
     * @param $datetime
     * @param $timezone
     * @return string
     */
    public static function ConvertToServerDate($datetime, $timezone): string
    {
        $datetime = Dates::Timestamp($datetime);
        $tz = new DateTimeZone('GMT');
        try {
            $date = new DateTime($datetime . ' ' . $timezone);
            $date->setTimezone($tz);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            Debug::Halt($e);
            exit;
        }
    }

    /**
     * @param $min_date
     * @param $max_date
     * @return int
     */
    public static function MonthsBetweenDates($min_date, $max_date): int
    {
        $date1 = $min_date;
        $date2 = $max_date;

        $ts1 = strtotime(Dates::Datestamp($date1));
        $ts2 = strtotime(Dates::Datestamp($date2));

        $year1 = (int)date('Y', $ts1);
        $year2 = (int)date('Y', $ts2);

        $month1 = (int)date('m', $ts1);
        $month2 = (int)date('m', $ts2);

        return (($year2 - $year1) * 12) + ($month2 - $month1);
    }

    /**
     * @param     $timeStamp
     * @param int $totalMonths
     *
     * @return int
     */
    public static function AddMonthToDate($timeStamp, int $totalMonths = 1): int
    {
        if (!is_numeric($timeStamp)) {
            $timeStamp = strtotime(self::Timestamp($timeStamp));
        }

        if (!is_numeric($totalMonths)) {
            $totalMonths = 0;
        }

        // You can add as many months as you want. mktime will accumulate to the next year.
        $thePHPDate = getdate($timeStamp); // Covert to Array
        $thePHPDate['mon'] = $thePHPDate['mon'] + $totalMonths; // Add to Month
        // Convert back to timestamp
        return mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
    }

    /**
     * @param     $timeStamp
     * @param int $totalDays
     *
     * @return int
     */
    public static function AddDayToDate($timeStamp, int $totalDays = 1): int
    {
        $timeStamp = strtotime(self::Datestamp($timeStamp));

        // You can add as many days as you want. mktime will accumulate to the next month / year.
        $thePHPDate = getdate($timeStamp);
        $thePHPDate['mday'] = $thePHPDate['mday'] + $totalDays;
        return mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
    }

    /**
     * @param     $timeStamp
     * @param int $totalYears
     *
     * @return int
     */
    public static function AddYearToDate($timeStamp, int $totalYears = 1): int
    {
        if (!is_numeric($timeStamp))
            $timeStamp = strtotime($timeStamp);

        $thePHPDate = getdate($timeStamp);
        $thePHPDate['year'] = $thePHPDate['year'] + $totalYears;
        return mktime($thePHPDate['hours'], $thePHPDate['minutes'], $thePHPDate['seconds'], $thePHPDate['mon'], $thePHPDate['mday'], $thePHPDate['year']);
    }


    /**
     * @param $year
     * @param $DayInYear
     * @return string|null
     */
    public static function DayOfYearToDate($year, $DayInYear): ?string
    {
        // http://webdesign.anmari.com/1956/calculate-date-from-day-of-year-in-php/

        $DayInYear = floor($DayInYear);
        $d = null;
        try {
            $d = new DateTime($year . '-01-01');
        } catch (Exception $e) {
            Debug::Halt($e);
        }
        date_modify($d, '+' . ($DayInYear - 1) . ' days');
        return self::Datestamp($d->getTimestamp());
    }

    /**
     * @param $date
     * @param string $last
     * @param string $next
     * @return array
     */
    public static function GetWeekRange($date, string $last = 'sunday', string $next = 'saturday'): array
    {
        if (!is_numeric($date)) {
            $ts = strtotime(Dates::Datestamp($date));
        } else {
            $ts = $date;
        }
        $start = (date('w', $ts) == 0) ? $ts : strtotime('last ' . $last, $ts);
        return [
            date('Y-m-d', $start),
            date('Y-m-d', strtotime('next ' . $next, $start))
        ];
    }

    /**
     * @param      $date
     * @param bool $debug
     *
     * @return DateCalcWeekDTO
     */
    public static function CalcWeek($date, bool $debug = false): DateCalcWeekDTO
    {
        if (strtotime($date) == 0) {
            return new DateCalcWeekDTO(null, null, null);
        }

        list($start_date, $end_date) = static::GetWeekRange($date);

        $t = strtotime($date);
        $y = date('Y', $t);
        $m = date('m', $t);


        if ($debug) {
            print_r(['date' => $date, 'y' => $y, 'm' => $m]);
        }
        $week_date = $start_date;
        $month_year = $m . $y;

        $w = date('W', strtotime($week_date));

        $week_year = $w . $y;
        return new DateCalcWeekDTO($week_date, $month_year, $week_year);
    }

    /**
     * @param $datetime
     *
     * @return int
     */
    public static function iCalDate2TimeStamp($datetime): int
    {
        return mktime($datetime['hour'], $datetime['min'], $datetime['sec'], $datetime['month'], $datetime['day'], $datetime['year']);
    }

    /**
     * @param $date
     * @param null $null
     * @return float|bool|int|string|null
     */
    public static function DateToInt($date, $null = null): float|bool|int|string|null
    {
        if ($date instanceof DateTime) {
            $temp = $date->getTimestamp();
            $str = $date->format('Y-m-d H:i:s');

            if (!$temp && !$str) { // don't interpret 1970-01-01 as not set
                return $null;
            }
            return $temp;
        }

        if (!$date) {
            return $null;
        }

        if (!is_numeric($date)) {
            $date = strtotime($date);
        }

        return $date;
    }

    /**
     * @param $date
     * @param null $null
     * @return string|null
     */
    public static function FancyDateTime($date, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'F jS, Y g:iA');
    }

    /**
     * @param $date
     * @param null $null
     * @return string|null
     */
    public static function FancyDate($date, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'F jS, Y');
    }

    /**
     * @param $date
     * @param null $null
     * @return string|null
     */
    public static function FancyDateB($date, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'F j, Y');
    }

    /**
     * @param $date
     * @param null $null
     * @return string|null
     */
    public static function ShortDate($date, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'n/j/y');
    }


    /**
     * @param $date
     * @param null $null
     * @return string|null
     */
    public static function ShortDateYear($date, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'M Y');
    }

    /**
     * @param $date
     * @param null $null
     * @return string|null
     */
    public static function LongDateYear($date, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'F Y');
    }

    /**
     * @param $start_at
     * @param $end_at
     *
     * @return string
     */
    public static function HourMinDiff($start_at, $end_at): string
    {
        if (!is_numeric($start_at)) $start_at = strtotime($start_at);
        if (!is_numeric($end_at)) $end_at = strtotime($end_at);

        $hours = floor(($end_at - $start_at) / 3600);
        $mins = ceil((($end_at - $start_at) / 3600 - $hours) * 60);
        return $hours . ':' . ($mins < 10 ? '0' : '') . $mins;
    }

    /**
     * @param mixed $time
     * @param int $offset
     * @return string|null
     */
    public static function AdjustedTime(mixed $time = 0, int $offset = 0): ?string
    {
        if (!$time) $time = time();

        if (!is_numeric($time)) {
            $time = strtotime($time);
        }

        if ($offset < 0) {
            $time = strtotime($offset . ' hour', $time);
        } else {
            $time = strtotime('+' . $offset . ' hour', $time);
        }

        global $CurrentUser;

        if (!is_null($CurrentUser) && $CurrentUser->id) {
            return strtotime(Dates::FromGMT($time, $CurrentUser->timezone_name));
        }

        return $time;
    }

    /**
     * @param null $date
     * @param null $null
     * @return string|null
     */
    public static function StandardDate($date = null, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'n/j/Y');
    }

    /**
     * @param null $date
     * @param null $null
     *
     * @return string|null
     */
    public static function DayMonthDate($date = null, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'n-j');
    }

    /**
     * @param null $date
     * @param null $null
     * @param null $offset
     * @return string|null
     */
    public static function StandardDateTime($date = null, $null = null, $offset = null): ?string
    {
        return self::Datestamp($date, $null, 'n/j/Y h:i A', $offset);
    }

    /**
     * @param null $date
     * @param null $null
     * @return string|null
     */
    public static function StandardTime($date = null, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'h:iA');
    }

    /**
     * @param DateTime|string $dateTime
     * @return string
     */
    public static function SQLDateTimeToString(mixed $dateTime): string
    {
        if (!is_object($dateTime)) {
            try {
                $dateTime = new DateTime($dateTime);
            } catch (Exception $ex) {
                Debug($ex);
            }
        }
        $t = $dateTime->format('Y-m-d H:i:s.u');
        return substr($t, 0, strlen($t) - 3);
    }

    /**
     * @param null $date
     * @param null $null
     * @param string $format
     * @return string|null
     */
    public static function Timestamp($date = null, $null = null, string $format = 'Y-m-d H:i:s'): ?string
    {
        if (!$format) {
            $format = 'Y-m-d H:i:s';
        }
        return self::Datestamp($date, $null, $format);
    }

    /**
     * @param null $date
     * @param null $null
     *
     * @return string|null
     */
    public static function TimeOnlystamp($date = null, $null = null): ?string
    {
        return self::Datestamp($date, $null, 'H:i');
    }

    /**
     * @param $date
     * @return string
     */
    public static function TimeElapsedString($date): string
    {
        $date = self::DateToInt($date);
        $etime = time() - $date;

        if ($etime < 1) {
            return 'just now';
        }

        $a = [365 * 24 * 60 * 60 => 'year',
            30 * 24 * 60 * 60 => 'month',
            24 * 60 * 60 => 'day',
            60 * 60 => 'hour',
            60 => 'minute',
            1 => 'second'
        ];
        $a_plural = ['year' => 'years',
            'month' => 'months',
            'day' => 'days',
            'hour' => 'hours',
            'minute' => 'minutes',
            'second' => 'seconds'
        ];

        foreach ($a as $secs => $str) {
            $d = $etime / $secs;
            if ($d >= 1) {
                if ($secs > 30 * 24 * 60 * 60) {
                    $r = number_format($d, 1);
                } else {
                    $r = round($d);
                }
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . ' ago';
            }
        }
        return '';
    }


    /**
     * @param $msg
     * @return string
     */
    public static function TimeString($msg): string
    {
        return time() . ': ' . self::Timestamp() . ': ' . $msg . PHP_EOL;
    }

    /**
     * @param $week
     * @param $year
     * @return array
     */
    public static function GetStartAndEndDate($week, $year): array
    {
        $time = strtotime("1 January $year", time());
        $day = date('w', $time);
        $time += ((7 * $week) + 1 - $day) * 24 * 3600;
        $return[0] = date('Y-n-j', $time);
        $time += 6 * 24 * 3600;
        $return[1] = date('Y-n-j', $time);
        return $return;
    }

    /**
     * @param null $date
     * @param null $null
     * @param string $format
     * @param null $offset
     * @return ?string
     */
    public static function Datestamp($date = null, $null = null, string $format = 'Y-m-d', $offset = null): ?string
    {
        if (!$format) {
            $format = 'Y-m-d';
        }

        if (is_null($null) && is_null($date)) {
            $date = time();
        }
        $date = self::DateToInt($date, $null);
        if ($date === $null) {
            return $null;
        }

        if (!$date && $null) {
            return $null;
        }

        if ($offset) {
            $date += $offset * 3600;
        }
        return date($format, $date);
    }

    /**
     * @param $date
     * @return string
     */
    public static function SolrTime($date): string
    {
        return self::Datestamp($date, null, 'Y-m-d\TH:i:s\Z');
    }

    /**
     * @param $date
     *
     * @return int
     */
    public function GetTimeStampFromDBDate($date): int
    {
        $dateArr = explode(' ', $date);
        $datePartArr = explode('-', $dateArr[0]);
        $timePartArr = explode(':', $dateArr[1]);

        return mktime($timePartArr[0], $timePartArr[1], $timePartArr[2], $datePartArr[1], $datePartArr[2], $datePartArr[0]);
    }

    /**
     * @param $time
     * @return string
     */
    public static function Age($time): string
    {
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }
        $diff = time() - $time;

        if ($diff < 15 * 60) {
            return floor(($diff + 30) / 60) . ' minutes';
        }

        if ($diff / 3600 < 24)
            return ceil($diff / 3600) . ' hours';

        return ceil($diff / 3600 / 24 - 0.5) . ' days';
    }

    /**
     * @return false|int
     */
    public static function GMTtime(): bool|int
    {
        return strtotime(gmdate('m/d/Y H:i:s'));
    }

    /**
     * @param $t
     * @param $timezone
     * @return string
     */
    public static function FromGMT($t, $timezone): string
    {
        $t = Dates::Timestamp($t);

        try {
            $dt = new DateTime($t);
        } catch (Exception $e) {
            return $t;
        }
        try {
            $dt->setTimezone(new DateTimeZone($timezone));
        } catch (Exception $ex) {
            return $dt->format('Y-m-d H:i:s');
        }

        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * @param $t
     * @param $timezone
     * @return string
     */
    public static function ToGMT($t, $timezone): string
    {
        $t = Dates::Timestamp($t);

        try {
            $dt = new DateTime($t, new DateTimeZone($timezone));
            $dt->setTimezone(new DateTimeZone('GMT'));
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            Debug('ToGMT');
        }
        return '';
    }

    /**
     * @param $date
     * @return bool
     */
    public static function IsWeekend($date): bool
    {
        if (!is_numeric($date)) {
            $date = strtotime($date);
        }
        return date('N', $date) >= 6;
    }

    /**
     * @param $start
     * @param $end
     * @return string|null
     */
    public static function DateRange($start, $end): ?string
    {
        if (!$end) {
            return self::StandardDate($start, '');
        }

        $year_month_start = self::Datestamp($start, null, 'Ym');
        $year_month_end = self::Datestamp($end, null, 'Ym');

        if (self::Datestamp($start) == self::Datestamp($end)) {
            if (self::Timestamp($start) == self::Timestamp($end)) {
                return self::Datestamp($start);
            }

            return self::Datestamp($start, null, 'F jS g:ia') . ' - ' . self::Datestamp($end, null, 'g:ia');
        }

        if (strcasecmp($year_month_end, $year_month_start) == 0) {
            return Dates::Datestamp($start, null, 'F jS') . ' - ' . self::Datestamp($end, null, 'jS, Y');
        }
        return Dates::StandardDate($start, '--') . ' - ' . Dates::StandardDate($end, '--');

    }
}

