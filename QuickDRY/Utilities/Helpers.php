<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 *
 */
class Helpers extends strongType
{
    /**
     * @return bool
     */
    public static function IsCLI(): bool
    {
        return (php_sapi_name() === 'cli');
    }

    /**
     * @param array $arr
     * @param bool $null_string
     * @param array|null $prop_definitions
     * @return array
     */
    public static function cleanArray(array $arr, bool $null_string = false, ?array $prop_definitions = null): array
    {
        // Cleans up an array of values so that it can ben
        // put into a database object and be saved into the database
        foreach ($arr as $k => $v) {
            if (is_object($v) && get_class($v) === 'DateTime') {
                $arr[$k] = isset($prop_definitions[$k]['type']) && strcasecmp($prop_definitions[$k]['type'], 'date') == 0 ? Dates::Datestamp($v) : Dates::Timestamp($v);
            }
            if ($null_string && is_null($v)) {
                $arr[$k] = 'null';
            }
        }
        return $arr;
    }

    /**
     * @return string
     */
    public static function EchoMemoryUsage(): string
    {
        $mem_usage = memory_get_usage(true);

        if ($mem_usage < 1024) {
            return $mem_usage . ' bytes';
        }
        if ($mem_usage < 1048576) {
            return round($mem_usage / 1024, 2) . ' kilobytes';
        }

        return round($mem_usage / 1048576, 2) . ' megabytes';
    }

    /**
     * @return bool
     */
    public static function IsWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }


    /**
     * @return string
     */
    public static function GUID(): string
    {
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    /**
     * @return string
     */
    public static function RecID(): string
    {
        return sprintf('%04X%04X%04X%04X%04X%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }


    /**
     * @param $filename
     * @return bool|string
     */

    public static function LoadFile($filename): bool|string
    {
        if (!file_exists($filename)) {
            Exception($filename . ' doesn\'t exist');
        }

        if (filesize($filename) == 0) {
            return '';
        }

        $fp = fopen($filename, 'r');
        $f = fread($fp, filesize($filename));
        fclose($fp);
        return $f;
    }

    /**
     * @param $cmd
     * @return int|string
     */
    public static function run_in_background($cmd): int|string
    {
        if (self::IsWindows()) {
            pclose(popen('start ' . $cmd, 'r'));
            return 0;
        }

        $PID = exec("nohup $cmd 1>/dev/null & echo $!");
        return ($PID);
    }

    /**
     * @param $file
     *
     * @return string
     */
    public static function BufferInclude($file): string
    {
        ob_start();
        include $file;
        $_PAGE_HTML = ob_get_contents();
        ob_end_clean();
        return $_PAGE_HTML;
    }


    /**
     * @param string $path
     * @param bool $realpath
     * @return array
     */
    public static function GetFolderFilesRecursive(string $path, bool $realpath = true): array
    {
        if(!is_dir($path)) {
            return [];
        }

        $directory = new RecursiveDirectoryIterator($path, FilesystemIterator::FOLLOW_SYMLINKS);
        $filter = new MyRecursiveFilterIterator($directory);
        $iterator = new RecursiveIteratorIterator($filter);
        $files = [];
        foreach ($iterator as $info) {
            $files[] = $realpath ? realpath($info->getPathname()) : $info->getPathname();
        }
        return $files;
    }
}


