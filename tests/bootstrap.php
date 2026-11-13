<?php
declare(strict_types=1);

// PHPUnit bootstrap for QuickDRY.
//
// Normally `composer install` provides vendor/autoload.php, which also loads
// QuickDRY/QuickDRY.php through autoload.files. The fallback below lets the
// tests run against a PHPUnit installed elsewhere (e.g. a global phpunit.phar)
// without the runtime dependencies.

$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (is_file($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'QuickDRY\\')) {
            $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $class) . '.php';
            if (is_file($file)) {
                require_once $file;
            }
        }
    });
}

if (!function_exists('DebugRedact')) {
    require_once dirname(__DIR__) . '/QuickDRY/QuickDRY.php';
}

// Fixtures shared by the tests (namespaced so they can't collide with app classes)
require_once __DIR__ . '/Fixtures/fixtures.php';
