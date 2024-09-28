<?php


use Bkucenski\Quickdry\Connectors\mssql\MSSQL_CodeGen;

$opts = 'h::';
$opts .= 'd::';
$opts .= 'c::';
$opts .= 'u::';
$opts .= 'v::';
$opts .= 'i::';
$opts .= 'm::';
$opts .= 'l::';
$opts .= 'f::';
$opts .= 'o::';
$opts .= 'j::';
$opts .= 's::';

$options = getopt($opts);

$_HOST = $options['h'] ?? '';
$_DATABASE = $options['d'] ?? '';
$_DATABASE_CONSTANT = $options['c'] ?? '';
$_USER_CLASS = $options['u'] ?? '';
$_USER_VAR = $options['v'] ?? '';
$_USER_ID_COLUMN = $options['i'] ?? '';
$_MASTERPAGE = $options['m'] ?? '';
$_LOWERCASE_TABLE = $options['l'] ?? '';
$_USE_FK_COLUMN_NAME = $options['f'] ?? '';
$_DATABASE_CLASS = $options['o'] ?? null;
$_GENERATE_JSON = $options['j'] ?? true;
$_SCHEMA_ONLY = $options['s'] ?? false;

if(!$_HOST || !$_DATABASE) {
    exit(basename(__FILE__) . ' usage: -h<host> -d<database> -c<database constant optional> -u<user class> -v<user variable> -i<user id column>' . PHP_EOL);
}

$CodeGen = new MSSQL_CodeGen();
$CodeGen->Init(
    $_DATABASE,
    $_DATABASE_CONSTANT,
    $_USER_CLASS,
    $_USER_VAR,
    $_USER_ID_COLUMN,
    $_MASTERPAGE,
    $_LOWERCASE_TABLE,
    $_USE_FK_COLUMN_NAME,
    $_DATABASE_CLASS,
    $_GENERATE_JSON,
    __DIR__ . '/..'
);
if($_SCHEMA_ONLY) {
	$CodeGen->DumpSchema();
} else {
	$CodeGen->GenerateClasses();
	$CodeGen->GenerateJSON();
}
