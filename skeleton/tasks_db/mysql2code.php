<?php
declare(strict_types=1);

use QuickDRY\Connectors\SQLCodeGen;
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
$opts .= 'p::';
$opts .= 't::';
$opts .= 'x::';
$opts .= 'g::';

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
$_USE_DATABASE_TYPE_PREFIX = (bool)($options['p'] ?? true);
$_JSON_INCLUDE = SQLCodeGen::ParseTableList($options['t'] ?? null);
$_JSON_EXCLUDE = SQLCodeGen::ParseTableList($options['x'] ?? null);
$_GENERATE_MANAGE = (bool)($options['g'] ?? true);

if (!$_DATABASE) {
    exit(
        basename(__FILE__) .
        ' usage: -d<database> -c<database constant optional>' .
        ' -u<user class> -v<user variable> -i<user id column>' .
        ' -p<use database type prefix: 1 or 0, default 1>' .
        ' -t<tables to generate /json endpoints for, comma-separated, wildcards ok; default all>' .
        ' -x<tables to skip, comma-separated, wildcards ok>' .
        ' -g<generate the admin-only /manage pages: 1 or 0, default 1>' .
        PHP_EOL
    );
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
    __DIR__ . '/..',
    $_USE_DATABASE_TYPE_PREFIX
);

// Only expose the tables your screens need, e.g. -t"invoice,project*" -x"user_mfa_code,api_*"
$CodeGen->SetJSONTables($_JSON_INCLUDE, $_JSON_EXCLUDE);
$CodeGen->SetGenerateManage($_GENERATE_MANAGE);

$CodeGen->GenerateClasses();
$CodeGen->GenerateJSON();