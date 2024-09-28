<?php

namespace test;

use Bkucenski\Quickdry\Utilities\strongType;
use test\classes\MSSQL_A;

const MSSQL_HOST = 'localhost';
const MSSQL_USER = 'root';
const MSSQL_PASS = '';
const MSSQL_BASE =  'test';



$res = MSSQL_A::QueryMap('SELECT * FROM test WHERE col = :val ', ['val' => 'test'], function($row) {
    return new strongType($row);
});

Debug($res);