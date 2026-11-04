<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

/**
 * Class MySQLBase
 */
class MySQL_C extends MySQL_Core
{
    protected static ?MySQL_Connection $connection = null;

    /**
     * @return void
     */
    protected static function _connect(): void
    {
        if (!defined('MYSQLC_HOST')) {
            exit('MYSQLC_HOST');
        }

        if (!defined('MYSQLC_USER')) {
            exit('MYSQLC_USER');
        }

        if (!defined('MYSQLC_PASS')) {
            exit('MYSQLC_PASS');
        }

        if (!defined('MYSQLC_PORT')) {
            exit('MYSQLC_PORT');
        }


        if (is_null(static::$connection)) {
            static::$DB_HOST = MYSQLC_HOST;
            static::$connection = new MySQL_Connection((string)MYSQLC_HOST, (string)MYSQLC_USER, (string)MYSQLC_PASS, MYSQLC_PORT);
        }
    }
}