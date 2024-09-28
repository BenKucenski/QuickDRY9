<?php

namespace test;

use Bkucenski\Quickdry\Utilities\strongType;
use test\classes\MySQL_A;

const MYSQL_HOST = 'localhost';
const MYSQL_USER = 'root';
const MYSQL_PASS = '';
const MYSQL_BASE =  'test';



$res = MySQL_A::QueryMap('SELECT * FROM test WHERE col = :val ', ['val' => 'test'], function($row) {
    return new strongType($row);
});

Debug($res);