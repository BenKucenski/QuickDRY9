<?php
declare(strict_types=1);
// require modules.php in command line scripts

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL ^ E_DEPRECATED);
date_default_timezone_set('GMT');

// Composer: loads QuickDRY (classes + global helpers like Debug(), Exception())
// and every other dependency.
require_once __DIR__ . '/vendor/autoload.php';

// Your global constants (SESSION_NAME, DATA_FOLDER, DB credentials, ...)
require_once __DIR__ . '/env.php';

/**
 * @return string
 */
function GUID(): string
{
    if (function_exists('com_create_guid') === true) {
        return trim(com_create_guid(), '{}');
    }

    return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X',
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(16384, 20479),
        mt_rand(32768, 49151),
        mt_rand(0, 65535),
        mt_rand(0, 65535),
        mt_rand(0, 65535)
    );
}

define('GUID', GUID());

// The application's own classes (pages\..., common\..., any folder in this project)
// are autoloaded from the project root by QuickDRY at startup.
