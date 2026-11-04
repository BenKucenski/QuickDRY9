<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

/**
 * Class Metrics
 */
class Metrics
{
    private static array $_vars = [];
    private static array $_count = [];
    private static array $_total = [];
    private static array $_running = [];

    public static ?bool $log = false;

    // --- Memory tracking ---
    private static array $_memVars   = []; // name => start bytes
    private static array $_memTotal  = []; // name => sum of deltas
    private static array $_memCount  = []; // name => # of stops/snapshots
    private static array $_memPeak   = []; // name => max observed usage during stop/snapshot
    private static array $_memRunning = []; // name => true if started

    private static float $global_start = 0;

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
        if(self::$log) {
            Log::Insert('Start: ' . $name);
        }

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
        if(self::$log) {
            Log::Insert('Stop: ' . $name);
        }

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
        // Memory reset as well for convenience
        static::$_memVars = [];
        static::$_memTotal = [];
        static::$_memCount = [];
        static::$_memPeak = [];
        static::$_memRunning = [];
    }

    // ------------------------------
    // Memory tracking helpers below
    // ------------------------------

    /**
     * Begin tracking memory for a task. Records the current memory usage (in bytes).
     * @param string $name
     * @param bool $realUsage pass true to use system-allocated size (memory_get_usage(true))
     */
    public static function StartMemory(string $name, bool $realUsage = true): void
    {
        if (isset(self::$_memRunning[$name])) {
            return;
        }
        self::$_memRunning[$name] = true;
        static::$_memVars[$name] = memory_get_usage($realUsage);
        static::$_memPeak[$name] = static::$_memPeak[$name] ?? 0;
        static::$_memTotal[$name] = static::$_memTotal[$name] ?? 0;
        static::$_memCount[$name] = static::$_memCount[$name] ?? 0;
    }

    /**
     * Stop tracking memory for a task. Adds the delta since StartMemory to totals and updates peak seen.
     * @param string $name
     * @param bool $realUsage
     */
    public static function StopMemory(string $name, bool $realUsage = true): void
    {
        if (!isset(self::$_memRunning[$name])) {
            return;
        }
        $current = memory_get_usage($realUsage);
        $start   = static::$_memVars[$name] ?? $current;
        $delta   = max(0, $current - $start);

        static::$_memTotal[$name] += $delta;
        static::$_memCount[$name] += 1;
        static::$_memPeak[$name]   = max(static::$_memPeak[$name] ?? 0, $current);

        unset(self::$_memRunning[$name], static::$_memVars[$name]);
    }

    /**
     * Toggle memory tracking for a task.
     * @param string $name
     * @param bool $realUsage
     */
    public static function ToggleMemory(string $name, bool $realUsage = true): void
    {
        if (isset(self::$_memRunning[$name])) {
            self::StopMemory($name, $realUsage);
        } else {
            self::StartMemory($name, $realUsage);
        }
    }

    /**
     * Record a point-in-time memory snapshot for a task (no need to Start/Stop).
     * Useful for logging at checkpoints.
     * @param string $name
     * @param bool $realUsage
     */
    public static function SnapshotMemory(string $name, bool $realUsage = true): void
    {
        $current = memory_get_usage($realUsage);
        static::$_memTotal[$name] = (static::$_memTotal[$name] ?? 0) + $current;
        static::$_memCount[$name] = (static::$_memCount[$name] ?? 0) + 1;
        static::$_memPeak[$name]  = max(static::$_memPeak[$name] ?? 0, $current);
    }

    /**
     * Current PHP process memory usage (bytes).
     * @param bool $realUsage
     * @return int
     */
    public static function GetCurrentMemory(bool $realUsage = true): int
    {
        return memory_get_usage($realUsage);
    }

    /**
     * Peak memory usage so far in this request (bytes).
     * @param bool $realUsage
     * @return int
     */
    public static function GetPeakMemory(bool $realUsage = true): int
    {
        return memory_get_peak_usage($realUsage);
    }

    /**
     * Render a memory report similar to ToString(), with averages, totals, and peaks.
     * @param string $unit One of 'B','KB','MB','GB'
     * @param bool $show_total Show grand totals line
     * @return string
     */
    public static function ToStringMemory(string $unit = 'MB', bool $show_total = true): string
    {
        $res = "memory usage by task\r\n";
        $res .= "--------------------\r\n";

        $grandTotal = 0;
        foreach (static::$_memTotal as $name => $totalBytes) {
            $count = static::$_memCount[$name] ?? 0;
            $avg   = ($count > 0) ? ($totalBytes / $count) : 0;
            $res  .= sprintf(
                "%s: %d @ %s avg\r\n",
                $name,
                $count,
                self::formatBytes($avg, $unit)
            );
            $grandTotal += $totalBytes;
        }

        $res .= "\r\ntotal memory by task\r\n";
        $res .= "--------------------\r\n";
        foreach (static::$_memTotal as $name => $totalBytes) {
            $pct = ($grandTotal > 0) ? ($totalBytes * 100 / $grandTotal) : 0;
            $peak = static::$_memPeak[$name] ?? 0;
            $res .= sprintf(
                "%s: %s (%.2f%%) | peak: %s\r\n",
                $name,
                self::formatBytes($totalBytes, $unit),
                $pct,
                self::formatBytes($peak, $unit)
            );
        }

        if (!empty(self::$_memRunning)) {
            $res .= "\r\nMemory Tracking Still Running\r\n";
            foreach (self::$_memRunning as $name => $_) {
                $res .= "$name:\r\n";
            }
        }

        if ($show_total) {
            $res .= 'grand total: ' . self::formatBytes($grandTotal, $unit) . "\r\n";
            $res .= 'process peak: ' . self::formatBytes(memory_get_peak_usage(true), $unit) . "\r\n\r\n";
        }

        return $res;
    }

    /**
     * Helper to format bytes in a consistent unit.
     * @param float|int $bytes
     * @param string $unit 'B','KB','MB','GB'
     * @return string
     */
    private static function formatBytes(float|int $bytes, string $unit = 'MB'): string
    {
        $unit = strtoupper($unit);
        $div = match ($unit) {
            'GB' => 1024 ** 3,
            'MB' => 1024 ** 2,
            'KB' => 1024,
            default => 1, // 'B'
        };
        return number_format($bytes / $div, 2) . $unit;
    }
}