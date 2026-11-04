<?php
declare(strict_types=1);

namespace QuickDRY\Connectors\mysql;

/**
 * Class MySQLBase
 */
class MySQL_A extends MySQL_Core
{
    protected static ?MySQL_Connection $connection = null;

    /**
     * @return void
     */
    protected static function _connect(): void
    {
        if (!defined('MYSQL_HOST')) {
            Exception('MYSQL_HOST');
        }

        if (!defined('MYSQL_USER')) {
            Exception('MYSQL_USER');
        }

        if (!defined('MYSQL_PASS')) {
            Exception('MYSQL_PASS');
        }

        if (!defined('MYSQL_PORT')) {
            Exception('MYSQL_PORT');
        }

        if (is_null(static::$connection)) {
            static::$DB_HOST = MYSQL_HOST;
            static::$connection = new MySQL_Connection(
                (string)MYSQL_HOST,
                (string)MYSQL_USER,
                (string)MYSQL_PASS,
                (int)MYSQL_PORT
            );
            if(defined('MYSQL_BASE') && MYSQL_BASE) {
                static::$connection->SetDatabase(MYSQL_BASE);
            }
        }
    }
}