<?php
declare(strict_types=1);

namespace QuickDRY\Math;

/**
 *
 */
class Statistics
{
    public float $m;
    public float $b;
    public float $r;
    public float $sd_x;
    public float $sd_y;
    public float $xm;
    public float $ym;

    /**
     * @param $data
     * @param $x_key
     * @param $y_key
     * @return bool
     */
    public function _rsquare($data, $x_key, $y_key): bool
    {
        $xm = static::mean($data, $x_key);
        $ym = static::mean($data, $y_key);
        $mb = static::mb($xm, $ym, $data, $x_key, $y_key);

        $this->m = $mb['m'];
        $this->b = $mb['b'];
        $this->xm = $xm;
        $this->ym = $ym;

        $n = 0;
        $d = 0;
        foreach ($data as $val) {
            $t = ($val[$y_key] - ($mb['m'] * $val[$x_key] + $mb['b']));
            $n += $t * $t;
            $t = $val[$y_key] - $ym;
            $d += $t * $t;
        }

        $this->r = $d != 0 ? 1.0 - $n / $d : 0;
        $this->sd_x = static::stddev($data, $x_key);
        $this->sd_y = static::stddev($data, $y_key);
        return true;
    }

    /**
     * @param array $list
     * @param string|null $key
     * @return float|int
     */
    public static function mean(array $list, ?string $key = null): float|int
    {
        $t = 0;
        if (sizeof($list) == 0) {
            return $t;
        }
        foreach ($list as $val) {
            if (!$key) {
                $t += $val;
            } else {
                if (!isset($val[$key])) {
                    Exception('QuickDRY Error: invalid data set');
                }
                $t += $val[$key];
            }
        }

        return $t / sizeof($list);
    }

    /**
     * @param $xm
     * @param $ym
     * @param $data
     * @param $x_key
     * @param $y_key
     * @return array
     */
    public static function mb($xm, $ym, $data, $x_key, $y_key): array
    {
        $mb = [];

        $xt = 0;
        $yt = 0;
        foreach ($data as $val) {
            $xt += ($val[$x_key] - $xm) * ($val[$y_key] - $ym);
            $yt += ($val[$x_key] - $xm) * ($val[$x_key] - $xm);
        }
        $mb['m'] = $yt != 0 ? $xt / $yt : 0;
        $mb['b'] = $ym - $mb['m'] * $xm;

        return $mb;
    }

    /**
     * @param $data
     * @param $x_key
     * @param $y_key
     * @return float
     */
    public static function rsquare($data, $x_key, $y_key): float
    {
        $xm = static::mean($data, $x_key);
        $ym = static::mean($data, $y_key);
        $mb = static::mb($xm, $ym, $data, 'x', 'y');

        $n = 0;
        $d = 0;
        foreach ($data as $val) {
            $t = ($val[$y_key] - ($mb['m'] * $val[$x_key] + $mb['b']));
            $n += $t * $t;
            $t = $val[$y_key] - $ym;
            $d += $t * $t;
        }

        return 1.0 - $n / $d;
    }

    /**
     * @param array $data
     * @param string|null $key
     * @param bool $minus_one
     * @return float
     */
    public static function stddev(array $data, ?string $key = null, bool $minus_one = true): float
    {
        if (!sizeof($data)) {
            return 0;
        }

        $m = static::mean($data, $key);
        $t = 0;
        foreach ($data as $val) {
            if (!$key) {
                $t += ($val - $m) * ($val - $m);
            } else {
                $t += ($val[$key] - $m) * ($val[$key] - $m);
            }
        }
        $t /= sizeof($data) - ($minus_one ? 1 : 0);

        return sqrt($t);
    }
}
