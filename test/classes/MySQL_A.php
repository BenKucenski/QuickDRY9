<?php

namespace test\classes;

use Bkucenski\Quickdry\Connectors\mysql\MySQL_Connection;
use Bkucenski\Quickdry\Connectors\mysql\MySQL_Core;
use const test\MYSQL_HOST;
use const test\MYSQL_PASS;
use const test\MYSQL_USER;

/**
 * Class MySQLBase
 */
class MySQL_A extends MySQL_Core
{
    /**
     * @return void
     */
    protected static function _connect(): void
    {
        if (!defined('MYSQL_HOST')) {
            exit('MYSQL_HOST');
        }

        if (!defined('MYSQL_USER')) {
            exit('MYSQL_USER');
        }

        if (!defined('MYSQL_PASS')) {
            exit('MYSQL_PASS');
        }

        if (!defined('MYSQL_PORT')) {
            exit('MYSQL_PORT');
        }

        if (is_null(static::$connection)) {
            static::$DB_HOST = MYSQL_HOST;
            static::$connection = new MySQL_Connection(
                MYSQL_HOST,
                MYSQL_USER,
                MYSQL_PASS,
                3306
            );
        }
    }
}