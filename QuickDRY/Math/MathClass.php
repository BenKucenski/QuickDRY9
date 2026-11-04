<?php
declare(strict_types=1);

namespace QuickDRY\Math;


use JetBrains\PhpStorm\ArrayShape;
use QuickDRY\Utilities\Dates;

/**
 * Class MathClass
 */
class MathClass
{
    /**
     * @param float $km
     * @return float
     */
    public static function KMtoMiles(float $km): float
    {
        return $km * 0.621371;
    }

    /**
     * @param array $arr
     * @return float
     */
    public static function Mean(array $arr): float
    {
        if (!sizeof($arr)) {
            return 0;
        }
        return array_sum($arr) / sizeof($arr);
    }

    /**
     * @param array $arr
     * @return float
     */
    public static function Median(array $arr): float
    {
        $count = count($arr); //total numbers in array
        $middleval = floor(($count - 1) / 2); // find the middle value, or the lowest middle value
        if ($count % 2) { // odd number, middle is the median
            $median = $arr[$middleval];
        } else { // even number, calculate avg of 2 medians
            $low = $arr[$middleval];
            $high = $arr[$middleval + 1];
            $median = (($low + $high) / 2);
        }
        return $median;
    }

    /**
     * @param array $arr
     * @return int
     */
    public static function Mode(array $arr): int
    {
        $summary = [];
        foreach ($arr as $val) {
            if (!isset($summary[$val])) {
                $summary[$val] = 0;
            }
            $summary[$val]++;
        }
        $maxs = array_keys($summary, max($summary));
        return (int)$maxs[0];
    }

    /**
     * @param float $raw
     * @param int $decimals
     * @return string
     */
    public static function ReportPercent(float $raw, int $decimals = 1): string
    {
        return number_format($raw * 100, $decimals);
    }

    /**
     * @param float $rate
     * @param float $principal
     * @param float $periods
     * @return float
     */
    public static function AccruedInterest(float $rate, float $principal, float $periods): float
    {
        return $principal * pow(1 + $rate, $periods) - $principal;
    }

    /**
     * @param float $rate
     * @param float $principal
     * @param float $payment
     * @param mixed $start_date
     * @param mixed $maturity_date
     * @return PrincipalInterest|null
     */
    public static function MonthsToRepay(
        float $rate,
        float $principal,
        float $payment,
        mixed $start_date = null,
        mixed $maturity_date = null
    ): ?PrincipalInterest
    {
        if ($start_date) {
            $start_date = strtotime(Dates::Datestamp($start_date));
        }

        if ($maturity_date) {
            $maturity_date = strtotime(Dates::Datestamp($maturity_date));
        }

        $res = new PrincipalInterest();
        $res->table = [];
        $res->principal = $principal;
        $res->principal_payment = $payment;
        $r = $rate / 12.0 / 100.0;

        // if the first month has more interest than the payment, there's a problem
        if (round($r * $principal, 2) >= $payment) {
            return null;
        }
        while ($res->principal > 0) {
            $interest_paid = round($r * $res->principal, 2);
            $p = $res->principal_payment - $interest_paid;
            $res->interest += $interest_paid;
            if ($res->principal < $p) {
                $p = $res->principal;
            }
            $res->principal -= $p;
            $res->month++;
            $row = [
                'month'          => $res->month,
                'payment'        => $payment,
                'principal'      => $p,
                'interest'       => $interest_paid,
                'total_interest' => $res->interest,
                'balance'        => $res->principal
            ];
            if ($start_date) {
                $row['month_date'] = Dates::Datestamp($start_date);
                $res->table[] = $row;

                $start_date = strtotime('+1 month', $start_date);
                if ($maturity_date) {
                    if ($start_date > $maturity_date) {
                        $start_date = strtotime('-1 month', $start_date);
                        $days = ($maturity_date - $start_date) / (3600 * 24);
                        $r = $rate / 365.0 / 100.0 * $days;
                        $interest_paid = $res->principal * $r;

                        $res->month++;
                        $res->interest += $interest_paid;
                        $row = [
                            'month_date'     => Dates::Datestamp($maturity_date),
                            'month'          => $res->month,
                            'payment'        => $res->principal + $interest_paid,
                            'principal'      => $res->principal,
                            'interest'       => $interest_paid,
                            'total_interest' => $res->interest,
                            'balance'        => 0
                        ];
                        $res->principal = 0;
                        $res->table[] = $row;
                    }
                }
            } else {
                $res->table[] = $row;
            }
        }
        $res->last_month = $res->table[sizeof($res->table) - 1]['month_date'] ?? null;
        return $res;
    }


    /**
     * @param float $rate
     * @param float $principal
     * @param float $term
     * @return float
     */
    public static function MonthlyPayment(float $rate, float $principal, float $term): float
    {
        return self::MonthlyPaymentForPeriod($rate, $principal, $term, 12);
    }

    /**
     * @param float $principal
     * @param float $payment
     * @param float $periods
     * @return float
     */
    public static function FindInterest(float $principal, float $payment, float $periods): float
    {
        $low = 0.0;
        $high = 100.0;
        $calc_p = 0;
        $cur = 0.0;
        $tries = 0;
        while (abs($calc_p - $payment) > 0.01 && $tries++ < 64) {
            $cur = ($high + $low) / 2.0;
            $calc_p = self::MonthlyPayment($cur, $principal, $periods / 12.0);
            if ($calc_p > $payment) $high = $cur; else $low = $cur;
        }
        return $cur;
    }

    /**
     * @param float $rate
     * @param float $principal
     * @param float $term
     * @param float $periods
     * @return float
     */
    public static function MonthlyPaymentForPeriod(float $rate, float $principal, float $term, float $periods): float
    {
        if ($rate == 0)
            return $principal / ($term * $periods);

        $L = $principal;
        //$I = $rate;
        $i = $rate / 100.0 / $periods;
        $T = $term;
        //$Y = $I * $T;
        //$X = 0.5 * $Y;
        $n = $periods * $T;
        $P = ($L * $i) / (1 - pow(M_E, -$n * log(1 + $i)));
        return round($P, 2);
    }

    /**
     * @param float $rate
     * @param float $principal
     * @param float $term
     * @return float
     */
    public static function TotalInterest(float $rate, float $principal, float $term): float
    {
        return self::TotalInterestForPeriod($rate, $principal, $term, 12);
    }

    /**
     * @param float $rate
     * @param float $principal
     * @param float $term
     * @param float $periods
     * @return float
     */
    public static function TotalInterestForPeriod(float $rate, float $principal, float $term, float $periods): float
    {
        $payment = self::MonthlyPaymentForPeriod($rate, $principal, $term, $periods);
        $res = self::MonthsToRepay($rate, $principal, $payment);
        if (!is_null($res)) {
            return $res->interest;
        }
        return 0;
    }

    /**
     * @param float $rate
     * @param float $principal
     * @param float $term
     * @return array
     */
    public static function Amortization(float $rate, float $principal, float $term): array
    {
        return self::AmortizationForPeriod($rate, $principal, $term, 12);
    }

    /**
     * @param float $rate
     * @param float $principal
     * @param float $term
     * @param float $periods
     * @return array
     */
    public static function AmortizationForPeriod(float $rate, float $principal, float $term, float $periods): array
    {
        $points = [];


        $point = new PrincipalInterest();
        $point->principal = $principal;
        $point->interest = 0;
        $points[] = $point;

        $payment = self::MonthlyPaymentForPeriod($rate, $principal, $term, $periods);
        $period_rate = $rate / 100.0 / $periods;
        for ($j = 0; $j < $term * $periods; $j++) {
            $interest = round($point->principal * $period_rate, 2);

            $point->interest_payment = $interest;
            $point->principal_payment = $payment - $interest;
            $point->principal -= $point->principal_payment;
            $point->interest += $interest;


            $p = new PrincipalInterest();
            $p->interest = $point->interest;
            $p->interest_payment = $point->interest_payment;
            $p->principal = $point->principal;
            $p->principal_payment = $point->principal_payment;
            $points[] = $p;
        }

        return $points;
    }

    /**
     * @param Debt $debt
     * @return array
     */
    public static function PayOff(Debt $debt): array
    {
        $history = [];
        $month = 0;

        $total = new PrincipalInterest();
        $total->principal = $debt->principal;

        while ($total->principal > 0 && $month < 360) {
            $month++;
            $point = new PrincipalInterest();
            $period_interest = $debt->interest_rate / 100.0 / 12.0 * $total->principal;

            $total->interest += $period_interest;

            if ($debt->payment < $total->principal + $period_interest) {
                $point->principal_payment = $debt->payment - $period_interest;
                $total->principal -= $point->principal_payment;
            } else {
                $point->principal_payment = $total->principal;
                $total->principal = 0;
            }

            $point->interest = $total->interest;
            $point->principal = $total->principal;
            $point->month = $month;
            $point->interest_payment = $period_interest;

            $history[] = $point;
        }
        return $history;
    }

    /**
     * @param Debt $account
     * @param int $months
     * @return array
     */
    public static function FutureValue(Debt $account, int $months): array
    {
        $history = [];
        $month = 0;

        $total = new PrincipalInterest();
        $total->principal = $account->principal;

        while ($month < $months) {
            $month++;
            $point = new PrincipalInterest();
            $period_interest = $account->interest_rate / 100.0 / 12.0 * $total->principal;

            $total->interest += $period_interest;

            $point->principal_payment = $account->payment;
            $total->principal += $point->principal_payment + $period_interest;

            $point->interest = $total->interest;
            $point->principal = $total->principal;
            $point->month = $month;
            $point->interest_payment = $period_interest;
            $history[] = $point;
        }
        return $history;
    }

    /**
     * @param Debt[] $debts
     * @return array
     */
    #[ArrayShape(['points' => 'array', 'history' => 'array'])] public static function Snowball(array $debts): array
    {
        $points = [];
        $point = new PrincipalInterest();
        $history = [];
        $cur_month = 0;
        $in_debt = true;

        $h = [];

        for ($j = 0; $j < sizeof($debts); $j++) {
            $d = new Debt();
            $d->interest_rate = $debts[$j]->interest_rate;
            $d->payment = 0;
            $d->principal = $debts[$j]->principal;
            $d->name = $debts[$j]->name;
            $h[] = $d;
        }
        $history[] = $h;

        while ($in_debt && $cur_month < 1200) {
            $point->principal = 0;
            $point->interest_payment = 0;
            $point->principal_payment = 0;

            $h = [];

            $rollover = 0.0;
            for ($j = 0; $j < sizeof($debts); $j++) {
                $interest = round($debts[$j]->principal * $debts[$j]->interest_rate / 100.0 / 12.0, 2);

                $point->interest += $interest;
                $point->principal += $debts[$j]->principal;

                $point->interest_payment += $interest;

                $debts[$j]->principal += $interest;
                $payment = $debts[$j]->payment;

                if ($payment > $debts[$j]->principal) {
                    $rollover += $payment - $debts[$j]->principal;
                    $point->principal_payment += $debts[$j]->principal - $interest;
                    $payment = $debts[$j]->principal;
                    $debts[$j]->principal = 0;
                } else {
                    $debts[$j]->principal -= $payment;
                    $point->principal_payment += $payment - $interest;
                }

                $d = new Debt();
                $d->interest_rate = $debts[$j]->interest_rate;
                $d->payment = $payment;
                $d->principal = $debts[$j]->principal;
                $d->name = $debts[$j]->name;
                $h[] = $d;
            }

            $has_debts = true;
            while ($rollover > 0 && $has_debts) {
                $remaining_debt = 0;
                for ($k = 0; $k < sizeof($debts); $k++) {
                    if ($debts[$k]->principal > 0) {
                        if ($debts[$k]->principal < $rollover) {
                            $rollover -= $debts[$k]->principal;
                            $point->principal_payment += $debts[$k]->principal;
                            $h[$k]->payment += $debts[$k]->principal;
                            $debts[$k]->principal = 0;
                        } else {
                            $debts[$k]->principal -= $rollover;
                            $point->principal_payment += $rollover;
                            $h[$k]->payment += $rollover;
                            $rollover = 0;
                            $remaining_debt++;
                        }
                    }
                }

                if ($remaining_debt == 0) {
                    $has_debts = false;
                }
            }

            if ($point->principal <= 0) {
                $in_debt = false;
            } else {
                $cur_month++;

                $history[] = $h;
                $p = new PrincipalInterest();
                $p->month = $cur_month;
                $p->interest = $point->interest;
                $p->interest_payment = $point->interest_payment;
                $p->principal = $point->principal;
                $p->principal_payment = $point->principal_payment;
                $points[] = $p;
            }
        }
        return ['points' => $points, 'history' => $history];
    }

    /**
     * @param mixed $current_time
     * @param float $current_price
     * @param mixed $start_time
     * @param float $start_price
     * @return float
     */
    public static function APY(mixed $current_time, float $current_price, mixed $start_time, float $start_price): float
    {
        if ($start_price == 0) {
            return 0;
        }

        if (!is_numeric($current_time)) {
            $current_time = strtotime($current_time);
        }

        if (!is_numeric($start_time)) {
            $start_time = strtotime($start_time);
        }

        if ($start_time == $current_time) {
            return 0;
        }

        return 100.0 * pow(
                ($current_price) /
                ($start_price), 1.0 / (($current_time - $start_time) * 1.0 / (365.0 * 24.0 * 3600.0))) - 100.0;
    }

    /**
     * @param float $present_value
     * @param float $payment
     * @param float $interest_rate
     * @param float $years
     * @param float $payments_per_year
     * @return float|int
     */
    public static function GetFutureValue(float $present_value, float $payment, float $interest_rate, float $years, float $payments_per_year): float|int
    {
        $interest_rate /= 100;
        $rk = $interest_rate / $payments_per_year;
        $int = pow((1 + $rk), $years * $payments_per_year);
        return $present_value * $int + $payment * (($int - 1) / $rk);
    }

    /**
     * @param float $present_value
     * @param float $future_value
     * @param float $payment
     * @param float $interest_rate
     * @param float $payments_per_year
     * @return float|int
     */
    public static function GetYearsToFutureValue(float $present_value, float $future_value, float $payment, float $interest_rate, float $payments_per_year): float|int
    {
        $interest_rate /= 100.0;
        $rk = $interest_rate / $payments_per_year;

        $x = $rk * $future_value + $payment;
        if ($present_value * $rk + $payment == 0) {
            return 0;
        }
        $x /= $present_value * $rk + $payment;

        return log($x, (1.0 + $rk)) / $payments_per_year;

    }

    /**
     * @param float $present_value
     * @param float $current_payment
     * @param float $interest_rate
     * @param float $months
     * @return float
     */
    public static function GetAdditionalPayment(float $present_value, float $current_payment, float $interest_rate, float $months): float
    {
        // https://www.sapling.com/8609716/calculate-months-pay-off-loan
        $interest_rate /= 100.0;
        $interest_rate /= 12.0;


        $pmt = -$present_value * $interest_rate / (exp(-$months * log(1.0 + $interest_rate)) - 1);
        $extra_payment = $pmt - $current_payment;

        $N = -(log(1 - (($present_value * $interest_rate) / $pmt)) / log(1 + $interest_rate));
        // $N should equal $months

        return $extra_payment;

    }
}
