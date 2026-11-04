<?php
declare(strict_types=1);

use QuickDRY\Connectors\mysql\MySQL_CodeGen;

$opts = 'd::';
$opts .= 'c::';
$opts .= 'u::';
$opts .= 'v::';
$opts .= 'i::';
$opts .= 'm::';
$opts .= 'l::';
$opts .= 'f::';
$opts .= 'o::';
$opts .= 'j::';

$options = getopt($opts);

$_DATABASE = $options['d'] ?? '';
$_DATABASE_CONSTANT = $options['c'] ?? '';
$_USER_CLASS = $options['u'] ?? '';
$_USER_VAR = $options['v'] ?? '';
$_USER_ID_COLUMN = $options['i'] ?? '';
$_MASTERPAGE = $options['m'] ?? '';
$_LOWERCASE_TABLE = (int)($options['l'] ?? 0);
$_USE_FK_COLUMN_NAME = (int)($options['f'] ?? 0);
$_DATABASE_CLASS = $options['o'] ?? null;
$_GENERATE_JSON = (bool)($options['j'] ?? true);

if(!$_DATABASE) {
    exit(basename(__FILE__) . ' usage: -d<database> -c<database constant optional> -u<user class> -v<user variable> -i<user id column>' . PHP_EOL);
}

include __DIR__ . '/../modules.php';


$CodeGen = new MySQL_CodeGen();
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
$CodeGen->GenerateClasses();
$CodeGen->GenerateJSON();
