<?php
declare(strict_types=1);

// QuickDRY bootstrap: global helpers and constants.
// Loaded automatically by Composer (autoload.files); classes are PSR-4 autoloaded.

use JetBrains\PhpStorm\NoReturn;
use QuickDRY\Utilities\Mailer;
use QuickDRY\Utilities\Metrics;
use QuickDRY\Web\BrowserOS;
use Composer\InstalledVersions;

/**
 * Root-namespace autoloader for the application using QuickDRY.
 * A class like pages\home\home or common\models\User is loaded from
 * <project root>/pages/home/home.php, <project root>/common/models/User.php, etc.
 * Any folder in the project root works without registering it anywhere.
 */
(static function (): void {
    $root = class_exists(InstalledVersions::class)
        ? realpath(InstalledVersions::getRootPackage()['install_path'])
        : false;

    if (!$root) {
        $root = dirname(__DIR__); // not installed through Composer (working in the QuickDRY repo itself)
    }

    if (!defined('QUICKDRY_PROJECT_ROOT')) {
        define('QUICKDRY_PROJECT_ROOT', $root);
    }

    spl_autoload_register(static function (string $class_name) use ($root): void {
        $file = $root . '/' . str_replace('\\', '/', $class_name) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
})();


/**
 * @param ...$args
 * @return void
 */
#[NoReturn]
function Exception(...$args): void
{
    Debug('Exception', $args);
}

/**
 * @param ...$args
 * @return void
 */
#[NoReturn]
function Testing(...$args): void
{
    Debug('Testing', $args);
}

/**
 * @param ...$args
 */
#[NoReturn]
function Debug(...$args): void
{
    if (!is_dir(DATA_FOLDER . '/logs')) {
        mkdir(DATA_FOLDER . '/logs');
    }

    $code = time() . '.' . rand(0, 1000000);
    $file = DATA_FOLDER . '/logs/' . $code . '.txt';
    $data = json_encode([
        'server'    => $_SERVER ?? null,
        'session'   => $_SESSION ?? null,
        'data'      => $args,
        'backtrace' => debug_backtrace(),
    ], JSON_PRETTY_PRINT);
    file_put_contents($file, $data);

    if (defined('SEND_DEBUG_EMAILS') && SEND_DEBUG_EMAILS) {
        $email = Mailer::Queue(
            SMTP_DEBUG_EMAIL,
            SMTP_DEBUG_EMAIL,
            defined('JS_ERROR') && JS_ERROR ? 'JS Error' : 'PHP Error',
            (string)$data
        );
        $email->Send();


    }


    if (!defined('CONST_OUTPUT_ERRORS') || !CONST_OUTPUT_ERRORS) {
        if (function_exists('post_debug_handler')) {
            post_debug_handler();
        }
        exit('<p>An Error Occurred: ' . $code . '</p>');
    }

    dd(['data' => $args, 'backtrace' => debug_backtrace()]);
}

/**
 * @param object $object
 * @return string
 */
function get_base_class(object $object): string
{
    $fullClass = get_class($object);
    $parts = explode('\\', $fullClass);
    return end($parts);
}

/**
 * @param string|null $data
 * @return string|null
 */
function base64_encode_id_safe(?string $data): ?string
{
    if (!$data) {
        return null;
    }

    $encoded = base64_encode($data);
    // Make it safe for HTML IDs
    $safe = strtr($encoded, ['+' => '-', '/' => '_']);
    return rtrim($safe, '='); // remove padding
}

/**
 * @param string|null $safeData
 * @return string|null
 */
function base64_decode_id_safe(?string $safeData): ?string
{
    if (!$safeData) {
        return null;
    }

    // Restore to standard base64 format
    $base64 = strtr($safeData, ['-' => '+', '_' => '/']);
    // Add padding if needed
    $padLen = 4 - (strlen($base64) % 4);
    if ($padLen < 4) {
        $base64 .= str_repeat('=', $padLen);
    }
    return base64_decode($base64);
}


// BasePage
const PDF_PAGE_ORIENTATION_LANDSCAPE = 'landscape';
const PDF_PAGE_ORIENTATION_PORTRAIT = 'portrait';

// http://doc.qt.io/archives/qt-4.8/qprinter.html#PaperSize-enum
const PDF_PAGE_SIZE_A0 = 'A0';
const PDF_PAGE_SIZE_A1 = 'A1';
const PDF_PAGE_SIZE_A2 = 'A2';
const PDF_PAGE_SIZE_A3 = 'A3';
const PDF_PAGE_SIZE_A4 = 'A4';
const PDF_PAGE_SIZE_A5 = 'A5';
const PDF_PAGE_SIZE_A6 = 'A6';
const PDF_PAGE_SIZE_A7 = 'A7';
const PDF_PAGE_SIZE_A8 = 'A8';
const PDF_PAGE_SIZE_A9 = 'A9';

const PDF_PAGE_SIZE_B0 = 'B0';
const PDF_PAGE_SIZE_B1 = 'B1';
const PDF_PAGE_SIZE_B2 = 'B2';
const PDF_PAGE_SIZE_B3 = 'B3';
const PDF_PAGE_SIZE_B4 = 'B4';
const PDF_PAGE_SIZE_B5 = 'B5';
const PDF_PAGE_SIZE_B6 = 'B6';
const PDF_PAGE_SIZE_B7 = 'B7';
const PDF_PAGE_SIZE_B8 = 'B8';
const PDF_PAGE_SIZE_B9 = 'B9';
const PDF_PAGE_SIZE_B10 = 'B10';

const PDF_PAGE_SIZE_C5E = 'C5E';
const PDF_PAGE_SIZE_COMM10E = 'Comm10E';
const PDF_PAGE_SIZE_DLE = 'DLE';
const PDF_PAGE_SIZE_EXECUTIVE = 'Executive';
const PDF_PAGE_SIZE_FOLIO = 'Folio';
const PDF_PAGE_SIZE_LEDGER = 'Ledger';
const PDF_PAGE_SIZE_LEGAL = 'Legal';
const PDF_PAGE_SIZE_LETTER = 'Letter';
const PDF_PAGE_SIZE_TABLOID = 'Tabloid';

const REQUEST_VERB_GET = 'GET';
const REQUEST_VERB_POST = 'POST';
const REQUEST_VERB_PUT = 'PUT';
const REQUEST_VERB_DELETE = 'DELETE';
const REQUEST_VERB_HISTORY = 'HISTORY';
const REQUEST_VERB_FIND = 'FIND';

const REQUEST_EXPORT_CSV = 'CSV';
const REQUEST_EXPORT_PDF = 'PDF';
const REQUEST_EXPORT_JSON = 'JSON';
const REQUEST_EXPORT_DOCX = 'DOCX';
const REQUEST_EXPORT_XLS = 'XLS';

// YesNo
const SELECT_NO = 1;
const SELECT_YES = 2;

Metrics::StartGlobal();
BrowserOS::Configure();


/**
 * Return arrays with only different values, side by side.
 *
 * Ignores superficial numeric differences (like 4871.87 vs 4871.8700).
 *
 * @param array $a
 * @param array $b
 * @param float $tolerance Precision tolerance for numeric comparison
 * @return array
 */
function arrays_diff_side_by_side(array $a, array $b, float $tolerance = 0.000001): array
{
    $result = [];

    foreach (array_keys(array_merge($a, $b)) as $key) {
        $valueA = $a[$key] ?? null;
        $valueB = $b[$key] ?? null;

        if (is_array($valueA) && is_array($valueB)) {
            // Recursively diff sub-arrays
            $diff = arrays_diff_side_by_side($valueA, $valueB, $tolerance);
            if (!empty($diff)) {
                $result[$key] = $diff;
            }
        } elseif ($valueA !== $valueB) {
            $bothNumeric = is_numeric($valueA) && is_numeric($valueB);

            // If both are numeric, compare with tolerance
            if ($bothNumeric && abs((float)$valueA - (float)$valueB) <= $tolerance) {
                continue; // difference is superficial, ignore it
            }

            // Keep only if they really differ
            $result[$key] = [
                'a' => $valueA,
                'b' => $valueB,
            ];
        }
    }

    return $result;
}

/**
 * Deep compare two arrays: same keys and same values.
 *
 * @param array $a
 * @param array $b
 * @return bool
 */
function arrays_are_equal(array $a, array $b): bool
{
    // Check that both have the same keys
    if (array_keys($a) !== array_keys($b)) {
        return false;
    }

    foreach ($a as $key => $valueA) {
        $valueB = $b[$key];

        if (is_array($valueA) && is_array($valueB)) {
            // Recursively compare sub-arrays
            if (!arrays_are_equal($valueA, $valueB)) {
                return false;
            }
        } elseif ($valueA !== $valueB) {

            // Mismatch for scalar values
            return false;
        }
    }

    return true;
}

/**
 * @param string $label
 * @param bool $real
 * @return void
 */
function mem(string $label, bool $real = true): void
{
    $cur = memory_get_usage($real);
    $peak = memory_get_peak_usage($real);
    echo sprintf("[%s] cur=%0.2fMB peak=%0.2fMB (real=%s)\n",
        $label, $cur / 1048576, $peak / 1048576, $real ? 'true' : 'false');
}

/**
 * Coerce a raw value into the given ReflectionType.
 *
 * Supports:
 * - Builtins: int, float, bool, string, array
 * - DateTimeInterface (DateTime/DateTimeImmutable)
 * - Enums (PHP 8.1+), backed or pure
 * - Objects (left as-is unless DateTime/Enum)
 * - Union types and nullables
 */
function coerce_to_reflection_type($value, ?ReflectionType $type)
{
    if (!$type) {
        return $value; // untyped property
    }

    // Union type: try each until one works
    if ($type instanceof ReflectionUnionType) {
        foreach ($type->getTypes() as $t) {
            try {
                return coerce_to_reflection_type($value, $t);
            } catch (Throwable $e) {
                // try next
            }
        }
        // If none worked, return as-is
        return $value;
    }

    // Named (possibly nullable)
    /** @var ReflectionNamedType $type */
    $allowsNull = $type->allowsNull();
    if ($value === null || $value === '' && $allowsNull) {
        return null;
    }

    $name = $type->getName();

    // Built-in scalars/array
    if ($type->isBuiltin()) {
        switch ($name) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                // Handles "true", "false", "1", "0", "yes", "no"
                $b = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                return $b ?? false;
            case 'string':
                return (string)$value;
            case 'array':
                // Try JSON first if string
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                }
                return is_array($value) ? $value : [$value];
            case 'object':
                return is_object($value) ? $value : (object)$value;
            case 'mixed':
            default:
                return $value;
        }
    }

    // Class types
    // DateTime / DateTimeImmutable / DateTimeInterface
    if (is_a($name, DateTimeInterface::class, true)) {
        if ($value === '' || $value === null) {
            return null; // keep null if allowed
        }
        // Choose an immutable default
        try {
            return new DateTimeImmutable((string)$value);
        } catch (Throwable $e) {
            return null;
        }
    }

    // Enums (PHP 8.1+)
    if (function_exists('enum_exists') && enum_exists($name)) {
        // Backed enums: tryFrom on string/int; Pure enums: match by case name
        if (is_subclass_of($name, BackedEnum::class)) {
            $res = $name::tryFrom($value);
            if ($res !== null) return $res;
        } else {
            // Pure enum: accept exact case name
            foreach ($name::cases() as $case) {
                if ((string)$value === $case->name) {
                    return $case;
                }
            }
        }
        // Fallback if invalid; return null if allowed
        return null;
    }

    // Other objects: leave as-is; caller can hydrate later if needed
    return $value;
}

/**
 * @param int $limit
 * @return array
 */
function safeBacktrace(int $limit = 20): array
{
    $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);
    $seen = new SplObjectStorage();
    $normalize = function ($v) use (&$normalize, $seen) {
        if (is_object($v)) {
            if ($seen->contains($v)) return '*RECURSION*';
            $seen->attach($v);
            return ['__class' => get_class($v)];
        }
        if (is_array($v)) {
            // cap size and avoid deep recursion
            $out = [];
            $count = 0;
            foreach ($v as $k => $vv) {
                if ($count++ > 10) { $out['...'] = '*TRUNCATED*'; break; }
                $out[$k] = is_scalar($vv) || $vv === null ? $vv : $normalize($vv);
            }
            return $out;
        }
        return is_resource($v) ? '*RESOURCE*' : $v;
    };

    foreach ($bt as &$frame) {
        if (isset($frame['args'])) {
            $frame['args'] = array_map($normalize, $frame['args']);
        }
        // Drop huge locals if present (Xdebug)
        unset($frame['params'], $frame['locals']);
    }
    return $bt;
}