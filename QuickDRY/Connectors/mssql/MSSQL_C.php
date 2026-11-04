<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mssql;

/**
 * Class MSSQL_Base
 */
class MSSQL_C extends MSSQL_Core
{
    protected static ?MSSQL_Connection $connection = null;

    /**
     * @return void
     */
    protected static function _connect(): void
    {
        if (!defined('MSSQLC_HOST')) {
            exit('MSSQLC_HOST');
        }
        if (!defined('MSSQLC_USER')) {
            exit('MSSQLC_USER');
        }
        if (!defined('MSSQLC_PASS')) {
            exit('MSSQLC_PASS');
        }

        if (is_null(static::$connection)) {
            static::$DB_HOST = MSSQLC_HOST;
            static::$connection = new MSSQL_Connection(MSSQLC_HOST ?? '', MSSQLC_USER ?? '', MSSQLC_PASS ?? '');
        }
    }
}
