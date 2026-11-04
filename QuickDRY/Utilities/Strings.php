<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use JetBrains\PhpStorm\NoReturn;
use SimpleXMLElement;


/**
 * Class Strings
 */
class Strings extends strongType
{
    /**
     * @param array $array
     * @param string $targetKey
     * @param string $newKey
     * @param mixed $newValue
     * @return array
     */
    public static function insertBeforeKey(array $array, string $targetKey, string $newKey, mixed $newValue): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if ($key === $targetKey) {
                $result[$newKey] = $newValue;
            }
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * @param array $array
     * @param string $targetKey
     * @param string $newKey
     * @param mixed $newValue
     * @return array
     */
    public static function insertAfterKey(
        array  &$array,
        string $targetKey,
        string $newKey,
        mixed  $newValue
    ): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $result[$key] = $value;

            if ($key === $targetKey) {
                $result[$newKey] = $newValue;
            }
        }

        $array = $result;

        return $result;
    }

    /**
     * @param string|null $emails
     * @return array
     */
    public static function SplitEmails(?string $emails): array
    {
        $list = [];
        if (!$emails) {
            return $list;
        }
        $temp = explode(';', str_replace([',', '/'], ';', $emails));
        foreach ($temp as $item) {
            if (trim($item)) {
                $list [] = trim($item);
            }
        }
        return $list;
    }

    /**
     * @param array $arr
     */
    public static function SortArrayByValueLength(array &$arr): void
    {
        usort($arr, function ($a, $b) {
            return strlen($b) - strlen($a);
        });
    }

    /**
     * @param $str
     * @return array|string|string[]|null
     */
    public static function RemoveQuotes($str): array|string|null
    {
        // https://stackoverflow.com/questions/9734758/remove-quotes-from-start-and-end-of-string-in-php
        return preg_replace('~^[\'"]?(.*?)[\'"]?$~', '$1', $str);
    }

    /**
     * @param $str
     * @return string
     */
    public static function ExcelTitleOnly($str): string
    {
        return self::Truncate(preg_replace('/\s+/i', ' ', preg_replace('/[^a-z0-9\s]/i', ' ', trim($str))), 31, false, false);
    }

    // https://stackoverflow.com/questions/3109978/display-numbers-with-ordinal-suffix-in-php

    /**
     * @param $number
     * @return string
     */
    public static function Ordinal($number): string
    {
        $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
            return $number . 'th';
        } else {
            return $number . $ends[$number % 10];
        }
    }

    /**
     * @param $filename
     * @param bool $clean_header
     * @param bool $has_header
     * @return array
     */
    public static function CSVToAssociativeArray($filename, bool $clean_header = false, bool $has_header = true): array
    {
        if (!file_exists($filename)) {
            return [];
        }
        return self::CSVArrayToAssociativeArray(file($filename), $clean_header, $has_header);
    }

    /**
     * @param $array
     * @param bool $clean_header
     * @param bool $has_header
     * @return array
     */
    public static function CSVArrayToAssociativeArray($array, bool $clean_header = false, bool $has_header = true): array
    {
        if (!is_array($array)) {
            $array = explode("\n", trim($array));
        }

        $rows = array_map('str_getcsv', $array);
        if (!$has_header) {
            return $rows;
        }

        $header = array_shift($rows);
        if ($clean_header) {
            foreach ($header as $i => $item) {
                $item = preg_replace('/[^a-z0-9]/i', ' ', $item);
                $item = preg_replace('/\s+/i', ' ', $item);
                $item = trim($item);
                $item = str_replace(' ', '_', $item);
                $header[$i] = strtolower($item);
            }
        }
        $csv = [];
        foreach ($rows as $row) {
            if (sizeof($header) != sizeof($row)) {
                continue;
            }
            $csv[] = array_combine($header, $row);
        }
        return $csv;
    }

    /**
     * @param $tsv
     * @return array
     */
    public static string $_SEPARATOR;

    /**
     * @param $tsv
     * @param string $separator
     * @return array
     */
    public static function TSVToArray($tsv, string $separator = "\t"): array
    {
        self::$_SEPARATOR = $separator;
        // https://stackoverflow.com/questions/4801895/csv-to-associative-array
        // https://stackoverflow.com/questions/28690855/str-getcsv-on-a-tab-separated-file
        /* Map Rows and Loop Through Them */
        $rows = array_map(function ($v) {
            $escape = '\\';
            return str_getcsv($v, self::$_SEPARATOR ?? ',', '"', $escape);
        }, explode("\n", $tsv));
        $header = array_shift($rows);
        $n = sizeof($header);
        $csv = [];
        foreach ($rows as $row) {
            $m = sizeof($row);
            for ($j = $m; $j < $n; $j++) {
                $row[] = ''; // fill in missing fields with emptry strings
            }
            if (sizeof($row) != $n) {
                continue;
            }
            $csv[] = array_combine($header, $row);
        }
        return $csv;
    }

    /**
     * @param string $tsv
     * @param callable|null $mapping_function
     * @param string|null $filename
     * @param string|null $class
     * @param bool $ignore_errors
     * @return array
     */
    public static function TSVToArrayMap(
        string    &$tsv,
        ?callable $mapping_function = null,
        ?string   $filename = null,
        ?string   $class = null,
        bool      $ignore_errors = false
    ): array
    {
        $tsv = trim($tsv); // remove trailing whitespace
        // https://stackoverflow.com/questions/4801895/csv-to-associative-array
        // https://stackoverflow.com/questions/28690855/str-getcsv-on-a-tab-separated-file
        /* Map Rows and Loop Through Them */
        $rows = array_map(function ($v) {
            return str_getcsv($v, "\t");
        }, explode("\n", $tsv));
        $header = array_shift($rows);
        $n = sizeof($header);
        $csv = [];
        foreach ($rows as $row) {
            $m = sizeof($row);
            for ($j = $m; $j < $n; $j++) {
                $row[] = ''; // fill in missing fields with empty strings
            }
            if (sizeof($row) != $n) {
                if (!$ignore_errors) {
                    Exception([$header, $row]);
                }
            }
            if ($mapping_function) {
                call_user_func($mapping_function, array_combine($header, $row), $filename, $class);
            } else {
                $csv[] = array_combine($header, $row);
            }
        }
        return $csv;
    }

    /**
     * @param $str
     * @return string
     */
    public static function KeyboardOnly($str): string
    {
        $str = preg_replace('/[^a-z0-9\!\@\#\$\%\^\&\*\(\)\-\=\_\+\[\]\\\{\}\|\;\'\:\"\,\.\/\<\>\\\?\ \r\n]/i', '', $str);
        return preg_replace('/\s+/i', ' ', $str);
    }

    /**
     * Convert an XML string to a PHP array without using eval.
     * - Returns array on success, or a string with an error message on failure.
     * - Attributes go under '@attributes'
     * - Text content goes under '_text' (if the element also has children/attributes)
     * - Repeated child elements with the same name become a numerically indexed array
     */
    public static function XMLtoArray($XML): array|string
    {
        if (!is_string($XML) || trim($XML) === '') {
            // Keep behavior simple and exception-free for "missing things"
            return [];
        }

        // Collect XML errors instead of throwing warnings/notices
        $prevUseInternal = libxml_use_internal_errors(true);

        // Security: block external entity loading; parse CDATA as text
        $options = LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NOCDATA;
        $sxml = simplexml_load_string($XML, 'SimpleXMLElement', $options);

        if ($sxml === false) {
            $errs = array_map(
                static fn($e) => trim($e->message),
                libxml_get_errors() ?: []
            );
            libxml_clear_errors();
            libxml_use_internal_errors($prevUseInternal);
            return 'Invalid XML' . (count($errs) ? ': ' . implode(' | ', $errs) : '');
        }

        $result = self::simplexmlToArray($sxml);

        libxml_clear_errors();
        libxml_use_internal_errors($prevUseInternal);

        return $result;
    }

    /**
     * Recursively convert a SimpleXMLElement to array.
     * - Scalars returned as strings when no attributes/children exist.
     * - If both children/attributes and text exist, text stored as '_text'.
     */
    public static function simplexmlToArray(SimpleXMLElement $elem): array|string
    {
        $out = [];

        // Attributes
        foreach ($elem->attributes() as $k => $v) {
            if (!isset($out['@attributes'])) {
                $out['@attributes'] = [];
            }
            $out['@attributes'][$k] = (string)$v;
        }

        // Children
        foreach ($elem->children() as $childName => $child) {
            $value = self::simplexmlToArray($child);

            if (array_key_exists($childName, $out)) {
                // Ensure we have a list for repeated children
                if (!is_array($out[$childName]) || !is_int(array_key_first($out[$childName]))) {
                    $out[$childName] = [$out[$childName]];
                }
                $out[$childName][] = $value;
            } else {
                $out[$childName] = $value;
            }
        }

        // Text content (trimmed)
        $text = trim((string)$elem);
        if ($text !== '') {
            if ($out === []) {
                // No attributes/children: just return the text scalar
                return $text;
            }
            // Preserve text alongside attributes/children
            $out['_text'] = $text;
        }

        return $out;
    }


    /**
     * @param $string
     * @param $ends_with
     * @param bool $case_sensitive
     * @return bool
     */
    public static function EndsWith($string, $ends_with, bool $case_sensitive = true): bool
    {
        if (!$case_sensitive) {
            return strcasecmp(substr($string, -strlen($ends_with), strlen($ends_with)), $ends_with) == 0;
        }
        return substr($string, -strlen($ends_with), strlen($ends_with)) === $ends_with;
    }

    /**
     * @param $remove
     * @param $string
     * @return string
     */
    public static function RemoveFromStart($remove, $string): string
    {
        $remove_length = strlen($remove);

        return substr($string, $remove_length, strlen($string) - $remove_length);
    }

    /**
     * @param $remove
     * @param $string
     * @return string
     */
    public static function RemoveFromEnd($remove, $string): string
    {
        $remove_length = strlen($remove);

        return substr($string, 0, strlen($string) - $remove_length);
    }

    /**
     * @param int $err_code
     * @return string
     */
    public static function JSONErrorCodeToString(int $err_code): string
    {
        return match ($err_code) {
            JSON_ERROR_NONE => ' - No errors',
            JSON_ERROR_DEPTH => ' - Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => ' - Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => ' - Unexpected control character found',
            JSON_ERROR_SYNTAX => ' - Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => ' - Malformed UTF-8 characters, possibly incorrectly encoded',
            JSON_ERROR_RECURSION => ' - One or more recursive references in the value to be encoded',
            JSON_ERROR_INF_OR_NAN => ' - One or more NAN or INF values in the value to be encoded',
            JSON_ERROR_UNSUPPORTED_TYPE => ' - 	A value of a type that cannot be encoded was given',
            JSON_ERROR_INVALID_PROPERTY_NAME => ' - A property name that cannot be encoded was given',
            JSON_ERROR_UTF16 => ' - Malformed UTF-16 characters, possibly incorrectly encoded',
            default => ' - Unknown error',
        };
    }

    /**
     * @param $value
     * @return string
     */
    public static function FormFilter($value): string
    {
        return str_replace('"', '\\"', $value);
    }

    /**
     * @param $js
     * @return string
     */
    public static function EchoJS($js): string
    {
        return addcslashes(str_replace('"', "'", $js), "'");
    }

    /**
     * @param $data
     * @return string
     */
    public static function ArrayToXML($data): string
    {
        $xml = '';
        foreach ($data as $key => $value) {
            $xml .= '<' . $key . '>' . $value . '</' . $key . '>';
        }

        return $xml;
    }

    /**
     * @param $value
     * @param int $brightness
     * @param int $max
     * @param int $min
     * @param string $thirdColorHex
     * @return string
     */
    public static function PercentToColor($value, int $brightness = 255, int $max = 100, int $min = 0, string $thirdColorHex = '00'): string
    {
        if ($value > $max) {
            $value = $max - ($value - $max);
            if ($value < $min) {
                $value = $min;
            }
        }
        // Calculate first and second color (Inverse relationship)
        $first = (1 - (($value - $min) / ($max - $min))) * $brightness;
        $second = (($value - $min) / ($max - $min)) * $brightness;

        // Find the influence of the middle color (yellow if 1st and 2nd are red and green)
        $diff = abs($first - $second);
        $influence = ($brightness - $diff) / 2;
        $first = intval($first + $influence);
        $second = intval($second + $influence);

        // Convert to HEX, format and return
        $firstHex = str_pad(dechex($first), 2, '0', STR_PAD_LEFT);
        $secondHex = str_pad(dechex($second), 2, '0', STR_PAD_LEFT);

        return $firstHex . $secondHex . $thirdColorHex;

        // alternatives:
        // return $thirdColorHex . $firstHex . $secondHex;
        // return $firstHex . $thirdColorHex . $secondHex;

    }

    /**
     * @param $string
     * @return string
     */
    public static function XMLEntities($string): string
    {
        return strtr(
            $string,
            [
                '<' => '&lt;',
                '>' => '&gt;',
                '"' => '&quot;',
                "'" => '&apos;',
                '&' => '&amp;',
            ]
        );
    }

    /**
     * @param $num
     * @return string
     */
    public static function BigInt($num): string
    {
        return sprintf('%.0f', $num);
    }

    /**
     * @param      $val
     * @param bool $dollar_sign
     * @param int $sig_figs
     * @return string
     */
    public static function Currency($val, bool $dollar_sign = true, int $sig_figs = 2): string
    {
        if (!is_numeric($val)) {
            return '--';
        }

        if ($val * 1.0 == 0) {
            return '--';
        }

        $res = number_format($val * 1.0, $sig_figs);
        if ($dollar_sign) {
            return '$' . $res;
        }
        return $res;
    }

    /**
     * @param      $val
     * @param bool $dollar_sign
     * @param int $sig_figs
     * @return string
     */
    public static function CurrencyAccountant($val, bool $dollar_sign = true, int $sig_figs = 2): string
    {
        if (!is_numeric($val)) {
            return '--';
        }

        if ($val * 1.0 == 0) {
            return '--';
        }

        if ($dollar_sign && $val < 0) {
            $res = number_format(-$val * 1.0, $sig_figs);
            return '($' . $res . ')';
        }

        $res = number_format($val * 1.0, $sig_figs);
        if ($dollar_sign) {
            return '$' . $res;
        }
        return $res;
    }

    /**
     * @param $str
     *
     * @return array|string|string[]
     */
    public static function EscapeXml($str): array|string
    {
        $str = str_replace('&', '&amp;', $str);
        $str = str_replace('>', '&gt;', $str);
        $str = str_replace('<', '&lt;', $str);
        $str = str_replace("\"", '&quot;', $str);
        return str_replace("'", '&apos;', $str);
    }

    /**
     * @param $desc
     *
     * @return string
     */
    public static function MakeUTF($desc): string
    {
        $desc = mb_convert_encoding($desc, 'UTF-8', 'ISO-8859-1');
        $desc = stripslashes($desc);
        return ($desc);
    }

    /**
     * @param $url
     * @param $key
     *
     * @return array|string|string[]|null
     */
    public static function RemoveStringVar($url, $key): array|string|null
    {
        return preg_replace('/' . $key . '=[^&]+?(&)(.*)/i', '$2', $url);
    }


    /**
     * @param $arg
     * @param $replaceWith
     *
     * @return array|string|string[]
     */
    public static function ReplaceSpecialChar($arg, $replaceWith): array|string
    {
        $replaceArr = ['&', '/', "\\", '*', '?', "\"", "\'", '<', '>', '|', ':', ' ', "'", '#', '%'];
        return str_replace($replaceArr, $replaceWith, $arg);
    }

    /**
     * @param mixed $val
     * @return float|int|string
     */
    public static function Numeric(mixed $val): float|int|string
    {
        // Treat only null/empty-string as empty; don't special-case "0"
        if ($val === null || trim((string)$val) === '') {
            return 0;
        }

        $s = trim((string)$val);

        // Handle scientific notation (both 'e' and 'E') without losing precision unnecessarily
        if (stripos($s, 'e') !== false) {
            // normalize to upper E and split
            [$coef, $exp] = array_pad(preg_split('/e/i', $s, 2), 2, null);
            if ($coef !== null && $exp !== null && is_numeric($coef) && preg_match('/^[+-]?\d+$/', $exp)) {
                // Expand scientific notation using string math to avoid float rounding
                $sign = ($coef[0] === '-') ? '-' : '';
                if ($coef[0] === '+' || $coef[0] === '-') {
                    $coef = substr($coef, 1);
                }
                $exp = (int)$exp;

                // split coefficient into integer+fractional digits
                $parts = explode('.', $coef, 2);
                $digits = $parts[0] . ($parts[1] ?? '');
                // remove leading zeros from digits but keep at least one
                $digits = ltrim($digits, '0');
                if ($digits === '') $digits = '0';

                $dotPos = strlen($parts[0]) + $exp;

                if ($dotPos <= 0) {
                    // 0.xxx form
                    $res = $sign . '0.' . str_repeat('0', -$dotPos) . $digits;
                } elseif ($dotPos >= strlen($digits)) {
                    // xxx000 form
                    $res = $sign . $digits . str_repeat('0', $dotPos - strlen($digits));
                } else {
                    // xxx.yyy form
                    $res = $sign . substr($digits, 0, $dotPos) . '.' . substr($digits, $dotPos);
                }

                // trim trailing zeros and lone dot
                $res = rtrim(rtrim($res, '0'), '.');
                return $res === '' || $res === '-0' ? 0 : $res;
            }
        }

        // Normalize thousands/locale-ish separators
        if (str_contains($s, ',') && str_contains($s, '.')) {
            // Both present → assume ',' is thousands separator → drop commas
            $s = str_replace(',', '', $s);
        } elseif (str_contains($s, ',') && !str_contains($s, '.')) {
            // Only commas present
            if (preg_match('/,\d{3}(?!\d)/', $s)) {
                // Pattern like 1,200 or 12,000 → treat commas as thousand separators
                $s = str_replace(',', '', $s);
            } else {
                // Otherwise assume comma is decimal separator
                $s = str_replace(',', '.', $s);
            }
        }
        // Strip everything except digits, dot, leading minus/plus
        $s = preg_replace('/(?!^)[+\-]/', '', $s);      // disallow extra signs
        $s = preg_replace('/[^0-9.\-+]/', '', $s);

        // Collapse multiple dots to a single dot (keep first)
        if (substr_count($s, '.') > 1) {
            $first = strpos($s, '.');
            $s = substr($s, 0, $first + 1) . str_replace('.', '', substr($s, $first + 1));
        }

        if (is_numeric($s)) {
            // Return a normalized string without scientific notation
            if (str_contains($s, '.')) {
                $out = rtrim(rtrim(sprintf('%.14F', (float)$s), '0'), '.'); // up to 14 dp by default
                return $out === '' ? '0' : $out;
            }
            // integer-ish
            return (string)(int)$s;
        }

        // Fallback: return the sanitized string as last resort
        return $s;
    }

    /**
     * @param $val
     * @param bool $return_orig_on_zero
     * @return float|int|string
     */
    public static function NumbersOnly($val, bool $return_orig_on_zero = true): float|int|string
    {
        $res = trim(preg_replace('/[^0-9\.]/i', '', $val));
        if (!$res) {
            return $return_orig_on_zero ? $val : 0;
        }
        return $res;
    }

    /**
     * @param $val
     * @return string
     */
    public static function NumericPhone($val): string
    {
        $res = trim(preg_replace('/[^0-9]/i', '', $val));
        if (!$res) {
            return $val;
        }
        return $res;
    }

    /**
     * @param string $val
     * @return string
     */
    public static function PhoneNumber(string $val): string
    {
        if (preg_match('/^\+?\d?(\d{3})(\d{3})(\d{4})$/', $val, $matches)) {
            return $matches[1] . '-' . $matches[2] . '-' . $matches[3];
        }
        return $val;
    }

    /**
     * @param $count
     * @param string $str
     * @param bool $increment
     * @return string
     */
    public static function GetPlaceholders($count, string $str = '{{}}', bool $increment = false): string
    {
        if ($increment) {
            $list = [];
            for ($j = 0; $j < $count; $j++) {
                $list[] = $str . $j;
            }
            return implode(', ', $list);
        }

        return implode(',', array_fill(0, $count, $str));
    }

    /**
     * @param $count
     * @return string
     */
    public static function GetSQLServerPlaceholders($count): string
    {
        return self::GetPlaceholders($count, '@');
    }

    /**
     * @param $val
     * @return int
     */
    public static function WordCount($val): int
    {
        return sizeof(explode(' ', preg_replace('/\s+/i', ' ', $val)));
    }

    /**
     * @param $hex
     * @return string
     */
    public static function Base16to10($hex): string
    {
        return base_convert($hex, 16, 10);
    }

    /**
     * @param $md5
     * @return string
     */
    public static function MD5toBase62($md5): string
    {
        $o = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $a = self::Base16to10(substr($md5, 0, 8));
        $b = self::Base16to10(substr($md5, 8, 8));

        $c = abs(($a * 1.0) ^ ($b * 1.0));

        $str = '';
        $m = strlen($o);
        while ($c > 1) {
            $str .= $o[$c % $m];
            $c = $c / $m;
        }
        $str .= $o[intval($c * $m)];

        $a = self::Base16to10(substr($md5, 16, 8));
        $b = self::Base16to10(substr($md5, 24, 8));
        $c = abs(($a * 1.0) ^ ($b * 1.0));
        $m = strlen($o);
        while ($c > 1) {
            $str .= $o[$c % $m];
            $c = $c / $m;
        }
        $str .= $o[$c * $m];


        return $str;
    }

    /**
     * @param string $str
     * @param int $length
     * @param bool $words
     * @param bool $dots
     * @return string
     */
    public static function Truncate(string $str, int $length, bool $words = false, bool $dots = true): string
    {
        if (strlen($str) > $length) {
            if ($words) {
                $s = strpos($str, ' ', $length);
                return substr($str, 0, $s) . ($dots ? '...' : '');
            } else {
                return substr($str, 0, $length) . ($dots ? '...' : '');
            }
        }
        return $str;
    }

    /**
     * @param $row
     * @return void
     * @deprecated This method is deprecated and should not be used.
     */
    #[NoReturn]
    private static function RowToJSON($row): void
    {
        Exception('Deprecated');


    }

    /**
     * @param $val
     * @return mixed
     */
    public static function fixBOOLs(&$val): mixed
    {
        if (is_string($val)) {
            if ($val === 'true') {
                $val = true;
            }
            if ($val === 'false') {
                $val = false;
            }
        }

        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::fixBOOLs($val[$k]);
            }
        }

        return $val;
    }

    /**
     * @param $input
     * @return void
     * @deprecated This method is deprecated and should not be used.
     */
    #[NoReturn]
    public static function FixJSON($input): void
    {
        Exception('Deprecated');
    }


    /**
     * @param $txt
     *
     * @return array|string|string[]
     */
    public static function InlineSafe($txt): array|string
    {
        $txt = str_replace('"', '\\"', str_replace("'", "\\'", $txt));
        $txt = str_replace("\r", '', $txt);
        return str_replace("\n", '<br/>', $txt);
    }

    /**
     * @param string $string
     * @param int $length
     *
     * @return string
     */
    public static function TrimString(string $string, int $length = 150): string
    {
        $string = trim(preg_replace('/\s+/', ' ', $string));
        $string = strip_tags($string);
        if (strlen($string) <= $length) {
            return $string;
        }
        return substr($string, 0, strpos(substr($string, 0, $length), ' ')) . '...';
    }


    /**
     * @param $company
     * @return string
     */
    public static function CleanCompanyName($company): string
    {
        $company = strtolower($company);
        $company = preg_replace('/\s+/', ' ', $company);
        $company = preg_replace('/[\.,\(\)\*]/', '', $company);
        $company = trim($company);

        $company = explode(' ', $company);
        foreach ($company as $i => $part) {
            if (in_array($part, [
                'n/a',
                'co',
                'corp',
                'corporation',
                'company',
                'llc',
                'of',
                'for',
                'the',
                '&',
                'inc',
                'na',
                //'mgt',
                //'mgmt',
                'llp',
                //'ny',
                'at',
                'ltd',
                'plc',
                'for',
                'in',
                //'dept',
                //'ctr',
                //'cntr',
                //'tech',
                //'assoc',
                //'assn',
                //'cty',
                //'gvmt',
                //'govt',
                'limited',
                'pvt',
                'and',
            ])) {
                unset($company[$i]);
                continue;
            }

            switch ($part) {
                case 'dept':
                    $company[$i] = 'Department';
                    break;
                case 'mgmt':
                case 'mgt':
                    $company[$i] = 'Management';
                    break;
                case 'ny':
                    $company[$i] = 'NewYork';
                    break;
                case 'cntr':
                case 'ctr':
                    $company[$i] = 'Center';
                    break;
                case 'tech':
                    $company[$i] = 'Technology';
                    break;
                case 'assn':
                case 'assoc':
                    $company[$i] = 'Association';
                    break;
                case 'cty':
                    $company[$i] = 'City';
                    break;
                case 'govt':
                case 'gvmt':
                    $company[$i] = 'Government';
                    break;
                case 'inst':
                    $company[$i] = 'Institute';
                    break;
            }
        }
        $company = trim(implode(' ', $company));
        return strtolower($company);
    }

    /**
     * @param string|null $address
     * @return ParsedUSAddress
     */
    public static function ParseUSAddress(?string $address): ParsedUSAddress
    {
        $result = new ParsedUSAddress();

        if (!$address) {
            return $result;
        }

        $address = trim($address);

        // Match: everything up to the last comma = street+city, then state, then zip
        if (!preg_match('/^(.+),\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)$/', $address, $matches)) {
            return $result;
        }

        $beforeState = trim($matches[1]);
        $result->state = $matches[2];
        $result->zip = $matches[3];

        if (str_contains($beforeState, ',')) {
            [$street, $city] = explode(',', $beforeState, 2);
            $result->street = trim($street);
            $result->city = trim($city);
        } elseif (preg_match('/^(.*\b(?:St|Ave|Dr|Blvd|Rd|Ln|Ct|Cir|Pl|Way|Pkwy|Hwy|Trl|Ter|Run|Loop|Pass|Pt|Sq|N|S|E|W|NE|NW|SE|SW)\.?)\s+([A-Za-z\s]+)$/i', $beforeState, $m)) {
            $result->street = trim($m[1]);
            $result->city = trim($m[2]);
        } else {
            $result->street = $beforeState;
        }

        return $result;
    }

    /**
     * @param string $text
     * @param string $replacement
     * @return array|string|string[]|null
     */
    public static function ToSearchable(string $text, string $replacement = ''): array|string|null
    {
        return preg_replace('/[^a-z0-9]/i', $replacement, strtolower($text));
    }

    /**
     * @param $num
     *
     * @return float|int
     */
    public static function NumberOfZeros($num): float|int
    {
        return $num != 0 ? floor(log10(abs($num))) : 1;

    }


    /**
     * @param $number
     *
     * @return string
     */
    public static function PhoneNumber2($number): string
    {
        if (!$number) {
            return '';
        }

        $number = preg_replace('/[^0-9]/i', '', $number);

        $m = strlen($number);
        $last = substr($number, $m - 4, 4);
        if ($m - 7 >= 0)
            $mid = substr($number, $m - 7, 3);
        else $mid = 0;
        if ($m - 10 >= 0)
            $area = substr($number, $m - 10, 3);
        else $area = '';

        if ($m - 10 > 0)
            $plus = '+' . substr($number, 0, $m - 10);
        else
            $plus = '';
        return $plus . '(' . $area . ') ' . $mid . '-' . $last;
    }


    /**
     * @param $text
     * @param bool $convert_urls
     * @return string
     */
    public static function StringToHTML($text, bool $convert_urls = false): string
    {
        $text = str_replace("\r", '', $text);
        $text = preg_replace('/\n+/i', "\n", $text);

        if ($convert_urls) {
            // https://stackoverflow.com/questions/1960461/convert-plain-text-urls-into-html-hyperlinks-in-php
            $url = '@(http)?(s)?(://)?(([a-zA-Z])([-\w]+\.)+([^\s\.]+[^\s]*)+[^,.\s])@';
            $text = preg_replace($url, '<a href="http$2://$4" target="_blank" title="$0">$0</a>', $text);
        }
        $t = explode("\n", $text);
        return '<p>' . implode('</p><p>', $t) . '</p>';
    }

    /**
     * @param string $html
     * @return array|string|string[]|null
     */
    public static function HTMLToString(string $html): array|string|null
    {
        $html = trim(strip_tags($html, '<p><br>'));
        $html = str_replace("\r", ' ', $html);
        $html = str_replace("\n", ' ', $html);
        $html = str_ireplace('&nbsp;', ' ', $html);
        $html = preg_replace('/\s+/', ' ', $html);

        $html = str_ireplace('<p>', '', $html);
        $html = str_ireplace('</p>', "\r\n", $html);
        $html = str_ireplace('<br>', "\r\n", $html);
        $html = str_ireplace('<br/>', "\r\n", $html);

        return preg_replace('/\ +/', ' ', $html);
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function StringToBR($text): string
    {
        $text = str_replace("\r", '', $text ?? '');
        $t = explode("\n", $text);
        return implode('<br/>', $t);
    }


    /**
     * @param string|null $num
     * @param int $dec
     * @param string $null
     *
     * @return string|null
     */
    public static function SmartNumberFormat(?string $num, int $dec = 2, string $null = '-'): ?string
    {
        if (!is_numeric($dec)) {
            return $num;
        }

        if (!is_numeric($num) || !$num) {
            return $null;
        }

        return number_format((float)$num, $dec);
    }

    /**
     * @param     $num
     * @param int $dec
     * @param string $comma
     * @return string|null
     */
    public static function FormNumberFormat($num, int $dec = 2, string $comma = ''): ?string
    {
        if (!is_numeric($num))
            return $num;
        return number_format($num, $dec, '.', $comma);
    }

    /**
     * @param string $pattern
     * @param int $multiplier
     * @param string $separator
     * @param bool|null $iterator
     * @return string
     */
    public static function StringRepeatCS(
        string $pattern,
        int    $multiplier,
        string $separator = ',',
        ?bool  $iterator = false
    ): string
    {
        $t = [];
        for ($j = 0; $j < $multiplier; $j++) {
            $t[] = $pattern . ($iterator ? $j : '');
        }
        return implode($separator, $t);
    }

    /**
     * @param $array
     * @param string $accessor
     * @param string $function
     * @return string
     */
    public static function CreateQuickList($array, string $accessor = '$item', string $function = 'Show'): string
    {
        $t = array_keys($array);
        $res = '';
        foreach ($t as $v) {

            $name = ucwords(strtolower($v));
            $res .= '<li><?php ' . $function . "('$name', $accessor->$v); ?></li>\n";
        }
        return $res;
    }

    /**
     * @param $var
     * @param string $default
     * @return string
     */
    public static function ShowOrDefault($var, string $default = 'n/a'): string
    {
        return $var ? htmlspecialchars_decode($var) : $default;
    }

    /**
     * @param $background_color
     * @return string
     */
    public static function FontColor($background_color): string
    {
        $rgb = Color::HexToRGB($background_color);
        $lumens = $rgb->Brightness();
        if ($lumens >= 130) {
            return '#000';
        }
        return '#fff';
    }

    /**
     * @param $str
     *
     * @return string
     */
    public static function CapsToSpaces($str): string
    {
        $results = [];
        preg_match_all('/[A-Z\d][^A-Z\d]*/', $str, $results);
        return implode(' ', $results[0]);
    }

    /**
     * @param array $array
     * @param string|null $parents
     * @param array|null $dest
     * @return void
     */
    public static function FlattenArray(array $array, ?string $parents = null, ?array &$dest = null): void
    {
        foreach ($array as $k => $v) {

            $k = preg_replace('/[^a-z0-9]/i', '', (string)$k);

            if (!is_array($v)) {
                $dest[$parents . $k] = $v;
                continue;
            }
            self::FlattenArray($v, $parents . $k . '_', $dest);
        }
    }

    /**
     * @param int $count
     * @param string $str
     * @param bool $increment
     * @return string
     */
    public static function GetValues(int $count, string $str, bool $increment): string
    {
        if ($increment) {
            $list = [];
            for ($j = 0; $j < $count; $j++) {
                $list[] = $str . $j;
            }
            return '(' . implode('),(', $list) . ')';
        }

        return '(' . implode('),(', array_fill(0, $count, $str)) . ')';
    }
}

