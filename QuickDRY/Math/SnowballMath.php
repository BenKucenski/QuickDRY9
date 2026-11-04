<?php
declare(strict_types=1);

namespace QuickDRY\Math;

use QuickDRY\Utilities\strongType;

/**
 * Class SnowballMath
 */
class SnowballMath extends strongType
{
    /* @var PrincipalInterest[] $points */
    public ?array $points = null;

    /* @var Debt[] $history */
    public ?array $history = null;

    private array $debts = [];
    private int $_last_id = 0;

    /**
     * @param Debt $cc
     */
    public function AddDebt(Debt $cc): void
    {
        $cc->id = $this->_last_id++;
        $this->debts[] = $cc;
    }

    /**
     * @param bool $desc
     */
    public function SortByInterest(bool $desc = true): void
    {
        $list = [];
        foreach ($this->debts as $debt) {
            $list[intval($debt->interest_rate * 100)][] = $debt;
        }

        if ($desc) {
            krsort($list);
        } else {
            ksort($list);
        }

        $this->debts = [];
        foreach ($list as $items) {
            foreach ($items as $debt) {
                $this->debts[] = $debt;
            }
        }
    }

    /**
     * @param bool $apply_rollover
     * @return self
     */
    public function DoSnowball(bool $apply_rollover = true): self
    {
        $debts = $this->debts;
        $points = [];
        $point = new PrincipalInterest();
        $history = [];
        $cur_month = 0;
        $in_debt = true;
        $base_rollover = 0.0;

        $h = [];

        for ($j = 0; $j < sizeof($debts); $j++) {
            if ($debts[$j]->principal == 0) {
                $base_rollover += $debts[$j]->payment;
                unset($debts[$j]);
            } else {
                $d = new Debt();
                $d->interest_rate = $debts[$j]->interest_rate;
                $d->payment = 0;
                $d->principal = $debts[$j]->principal;
                $d->name = $debts[$j]->name;
                $h[] = $d;
            }
        }

        $history[] = $h;

        while ($in_debt && $cur_month < 360 && $point->principal < 1000000000) {
            $point->principal = 0;
            $point->interest_payment = 0;
            $point->principal_payment = 0;

            $h = [];
            $rollover = $base_rollover;

            foreach ($debts as $j => $debt) {
                $interest = round($debt->principal * $debt->interest_rate / 100.0 / 12.0, 2);

                $point->interest += $interest;
                $point->principal += $debt->principal;

                $point->interest_payment += $interest;

                $debt->principal += $interest;
                $payment = $debt->payment;

                if ($payment > $debt->principal) {
                    $rollover += $payment - $debt->principal;
                    $point->principal_payment += $debt->principal - $interest;
                    $payment = $debt->principal;
                    $debt->principal = 0;
                } else {
                    $debt->principal -= $payment;
                    $point->principal_payment += $payment - $interest;
                }

                $d = new Debt();
                $d->interest_rate = $debt->interest_rate;
                $d->payment = $payment;
                $d->principal = $debt->principal;
                $d->name = $debt->name;
                $h[$j] = $d;
            }

            $total_rollover = $rollover;

            $debts = array_values($debts);
            $has_debts = true;
            while ($apply_rollover && $rollover > 0 && $has_debts) {
                $remaining_debt = 0;
                for ($k = 0; $k < sizeof($debts) && $rollover > 0; $k++) {
                    if(!isset($h[$k])) {
                        $h[$k] = new Debt();
                    }
                    if ($debts[$k]->principal > 0) {
                        if ($debts[$k]->principal < $rollover) {
                            $rollover -= $debts[$k]->principal;
                            $point->principal_payment += $debts[$k]->principal;
                            $h[$k]->payment += $debts[$k]->principal;
                            $h[$k]->principal = 0;
                            $debts[$k]->principal = 0;
                        } else {
                            $debts[$k]->principal -= $rollover;
                            $h[$k]->principal -= $rollover;
                            $point->principal_payment += $rollover;
                            $h[$k]->payment = $debts[$k]->payment + $rollover;
                            $rollover = 0;
                            $remaining_debt++;
                        }
                    }
                }
                if ($remaining_debt == 0)
                    $has_debts = false;

                $total_rollover = $rollover;
            }

            if ($point->principal <= 0)
                $in_debt = false;
            else {
                $cur_month++;

                $history[] = $h;
                $p = new PrincipalInterest();
                $p->rollover = $total_rollover;
                $p->month = $cur_month;
                $p->interest = $point->interest;
                $p->interest_payment = $point->interest_payment;
                $p->principal = $point->principal;
                $p->principal_payment = $point->principal_payment;
                $points[] = $p;
            }
        }
        return new self([
            'points' => $points,
            'history' => $history,
        ]);
    }
}