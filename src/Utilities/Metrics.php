<?php

namespace Bkucenski\Quickdry\Utilities;

/**
 * Class Metrics
 */
class Metrics
{
    private static array $_vars = [];
    private static array $_count = [];
    private static array $_total = [];
    private static array $_running = [];

    private static int $global_start = 0;

    /**
     * @return void
     */
    public static function StartGlobal(): void
    {
        static::$global_start = microtime(true);
    }

    /**
     * @return float|int
     */
    public static function GetGlobal(): float|int
    {
        return microtime(true) - static::$global_start;
    }

    /**
     * @param bool $show_total
     * @return string
     */
    public static function ToString(bool $show_total = true): string
    {
        $res = "individual task time (secs)\r\n";
        $res .= "--------------------\r\n";
        $total = 0;
        foreach (static::$_vars as $name => $last) {
            if (!isset(static::$_total[$name])) {
                static::$_total[$name] = 0;
            }

            if (!isset(static::$_count[$name])) {
                static::$_count[$name] = 0;
            }

            $res .= "$name: " . static::$_count[$name] . ' @ ' . (static::$_count[$name] && static::$_total[$name] ? static::$_total[$name]
                    / static::$_count[$name] : 0) . "secs\r\n";
            $total += static::$_total[$name];
        }
        $res .= "\r\ntime spent per task (secs)\r\n";
        $res .= "--------------------\r\n";
        foreach (static::$_vars as $name => $last) {
            $res .= "$name: " . static::$_total[$name] . ' (' . number_format($total ? static::$_total[$name] * 100 / $total : 0, 2) . "%)\r\n";
        }
        if (sizeof(self::$_running)) {
            $res .= "Still Running\r\n";
            foreach (static::$_running as $name => $value) {
                $res .= "$name: \r\n";
            }
        }
        if ($show_total) {
            $res .= "total time: $total\r\n\r\n";
        }

        return $res;
    }

    /**
     * @param $name
     */
    public static function Toggle($name): void
    {
        if (isset(self::$_running[$name])) {
            self::Stop($name);
        } else {
            self::Start($name);
        }
    }

    /**
     * @param $name
     */
    public static function Start($name): void
    {
        if (isset(self::$_running[$name])) {
            return;
        }

        self::$_running[$name] = true;
        static::$_vars[$name] = microtime(true);
    }

    /**
     * @param $name
     */
    public static function Stop($name): void
    {
        if (!isset(self::$_running[$name])) {
            return;
        }

        if (!isset(static::$_count[$name]))
            static::$_count[$name] = 0;
        if (!isset(static::$_total[$name]))
            static::$_total[$name] = 0;

        static::$_vars[$name] = microtime(true) - static::$_vars[$name];
        static::$_count[$name]++;
        static::$_total[$name] += static::$_vars[$name];
        unset(self::$_running[$name]);
    }

    /**
     * @return void
     */
    public static function Reset(): void
    {
        static::$_vars = [];
        static::$_count = [];
        static::$_total = [];
    }
}

