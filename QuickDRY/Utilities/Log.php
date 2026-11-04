<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

/**
 * Class Log
 */
class Log extends strongType
{
    private static ?LogFile $_log_file = null;

    /**
     *
     */
    private static function _init(): void
    {
        if (is_null(self::$_log_file)) {
            self::$_log_file = new LogFile();
        }
    }

    /**
     * @param $message
     * @param bool $write_to_file
     */
    public static function Insert($message, bool $write_to_file = true): void
    {
        self::_init();
        if (!defined('GUID')) {
            return;
        }

        self::$_log_file->Insert($_SERVER['SCRIPT_FILENAME'], $message, $write_to_file);
    }

    /**
     * @param $message
     * @return void
     */
    public static function Print($message): void
    {
        self::Insert($message);
    }

    /**
     * @param $message
     * @return void
     */
    public static function File($message): void
    {
        self::Insert($message);
    }
}