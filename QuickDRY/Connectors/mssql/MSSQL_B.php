<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

/**
 * Class MSSQL_Base
 */
class MSSQL_B extends MSSQL_Core
{
    protected static ?MSSQL_Connection $connection = null;

    /**
     * @return void
     */
    protected static function _connect(): void
    {
        if (!defined('MSSQLB_HOST')) {
            exit('MSSQLB_HOST');
        }
        if (!defined('MSSQLB_USER')) {
            exit('MSSQLB_USER');
        }
        if (!defined('MSSQLB_PASS')) {
            exit('MSSQLB_PASS');
        }

        if (is_null(static::$connection)) {
            static::$DB_HOST = MSSQLB_HOST;
            static::$connection = new MSSQL_Connection(MSSQLB_HOST ?? '', MSSQLB_USER ?? '', MSSQLB_PASS ?? '');
        }
    }

    /**
     * @param bool $val
     */
    public static function SetIgnoreDuplicateError(bool $val): void
    {
        self::_connect();
        self::$connection->IgnoreDuplicateError = $val;
    }

    /**
     * @return string|null
     */
    public static function _Table(): ?string
    {
        return static::$table;
    }
}
