<?php

namespace test\classes;

use Bkucenski\Quickdry\Connectors\mssql\MSSQL_Connection;
use Bkucenski\Quickdry\Connectors\mssql\MSSQL_Core;

use const test\MSSQL_BASE;
use const test\MSSQL_HOST;
use const test\MSSQL_PASS;
use const test\MSSQL_USER;

/**
 * Class MSSQL_Base
 */
class MSSQL_A extends MSSQL_Core
{
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
            static::$connection = new MSSQL_Connection(MSSQL_HOST, MSSQL_USER, MSSQL_PASS);
            if(defined('MSSQL_BASE') && MSSQL_BASE) {
                static::$connection->SetDatabase(MSSQL_BASE);
            }
        }
    }
}