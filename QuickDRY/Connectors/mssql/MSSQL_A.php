<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

/**
 * Class MSSQL_Base
 */
class MSSQL_A extends MSSQL_Core
{
    protected static ?MSSQL_Connection $connection = null;

    /**
     * @return void
     */
    protected static function _connect(): void
    {
        if (!defined('MSSQL_HOST')) {
            exit('MSSQL_HOST');
        }
        if (!defined('MSSQL_USER')) {
            exit('MSSQL_USER');
        }
        if (!defined('MSSQL_PASS')) {
            exit('MSSQL_PASS');
        }
        if (is_null(static::$connection)) {
            static::$DB_HOST = MSSQL_HOST;
            static::$connection = new MSSQL_Connection((string)MSSQL_HOST, (string)MSSQL_USER, (string)MSSQL_PASS);
            if(defined('MSSQL_BASE') && MSSQL_BASE) {
                static::$connection->SetDatabase(MSSQL_BASE);
            }
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