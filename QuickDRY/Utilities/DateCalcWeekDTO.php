<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;


/**
 *
 */
class DateCalcWeekDTO extends strongType
{
    public ?string $week_date, $month_year, $week_year;

    /**
     * @param string|null $week_date
     * @param string|null $month_year
     * @param string|null $week_year
     */
    public function __construct(?string $week_date, ?string $month_year, ?string $week_year)
    {
        $this->week_date = $week_date;
        $this->week_year = $week_year;
        $this->month_year = $month_year;
    }
}