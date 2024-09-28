<?php

namespace Bkucenski\Quickdry\Utilities;

use DateTime;
use Exception;
use Bkucenski\Quickdry\Connectors\SQL_Base;
use stdClass;

/**
 * Class Strings
 */
class Strings extends strongType
{
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
            return str_getcsv($v, self::$_SEPARATOR);
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
     * @param $tsv
     * @param null $mapping_function
     * @param string|null $filename
     * @param string|null $class
     * @param bool $ignore_errors
     * @return array
     */
    public static function TSVToArrayMap(&$tsv, $mapping_function = null, string $filename = null, string $class = null, bool $ignore_errors = false): array
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
                    Debug([$header, $row]);
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
     * @param $xml
     * @return mixed
     */
    public static function SimpleXMLToArray($xml): mixed
    {
        $xml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $json = json_encode($xml);
        return json_decode($json, TRUE);
    }

    /**
     * @param $XML
     * @return array|string
     */
    public static function XMLtoArray($XML): array|string
    {
        // https://stackoverflow.com/questions/3630866/php-parse-xml-string

        $xml_array = '';
        $multi_key = [];
        $multi_key2 = [];
        $level = [];
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $XML, $vals);
        xml_parser_free($xml_parser);
        $_tmp = '';
        foreach ($vals as $xml_elem) {
            $x_tag = $xml_elem['tag'];
            $x_level = $xml_elem['level'];
            $x_type = $xml_elem['type'];
            if ($x_level != 1 && $x_type == 'close') {
                if (isset($multi_key[$x_tag][$x_level]))
                    $multi_key[$x_tag][$x_level] = 1;
                else
                    $multi_key[$x_tag][$x_level] = 0;
            }
            if ($x_level != 1 && $x_type == 'complete') {
                if ($_tmp == $x_tag)
                    $multi_key[$x_tag][$x_level] = 1;
                $_tmp = $x_tag;
            }
        }

        foreach ($vals as $xml_elem) {
            $x_tag = $xml_elem['tag'];
            $x_level = $xml_elem['level'];
            $x_type = $xml_elem['type'];
            if ($x_type == 'open')
                $level[$x_level] = $x_tag;
            $start_level = 1;
            $php_stmt = '$xml_array';
            if ($x_type == 'close' && $x_level != 1)
                $multi_key[$x_tag][$x_level]++;
            while ($start_level < $x_level) {
                $php_stmt .= '[$level[' . $start_level . ']]';
                if (isset($multi_key[$level[$start_level]][$start_level]) && $multi_key[$level[$start_level]][$start_level])
                    $php_stmt .= '[' . ($multi_key[$level[$start_level]][$start_level] - 1) . ']';
                $start_level++;
            }
            $add = '';
            if (isset($multi_key[$x_tag][$x_level]) && $multi_key[$x_tag][$x_level] && ($x_type == 'open' || $x_type == 'complete')) {
                if (!isset($multi_key2[$x_tag][$x_level]))
                    $multi_key2[$x_tag][$x_level] = 0;
                else
                    $multi_key2[$x_tag][$x_level]++;
                $add = '[' . $multi_key2[$x_tag][$x_level] . ']';
            }
            if (isset($xml_elem['value']) && trim($xml_elem['value']) != '' && !array_key_exists('attributes', $xml_elem)) {
                if ($x_type == 'open')
                    $php_stmt_main = $php_stmt . '[$x_type]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
                else
                    $php_stmt_main = $php_stmt . '[$x_tag]' . $add . ' = $xml_elem[\'value\'];';
                eval($php_stmt_main);
            }
            if (array_key_exists('attributes', $xml_elem)) {
                if (isset($xml_elem['value'])) {
                    $php_stmt_main = $php_stmt . '[$x_tag]' . $add . '[\'content\'] = $xml_elem[\'value\'];';
                    eval($php_stmt_main);
                }
                foreach ($xml_elem['attributes'] as $value) {
                    try {
                        if (!is_array($xml_array)) {
                            $xml_array = [];
                        }

                        $php_stmt_att = $php_stmt . '[$x_tag]' . $add . '[$key] = $value;';
                        eval($php_stmt_att);
                    } catch (Exception $ex) {
                        Debug::Halt([$xml_array, $ex]);
                    }
                }
            }
        }
        return $xml_array;
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
        $firstHex = str_pad(dechex($first), 2, 0, STR_PAD_LEFT);
        $secondHex = str_pad(dechex($second), 2, 0, STR_PAD_LEFT);

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
        if ($dollar_sign)
            return '$' . $res;
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
        $desc = utf8_encode($desc);
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
     * @param $val
     * @return float|int|string
     */
    public static function Numeric($val): float|int|string
    {
        // handle scientific notation, force into decimal format
        if (stristr($val, 'E')) {
            $temp = explode('E', $val);
            if (sizeof($temp) == 2) {
                // https://stackoverflow.com/questions/1471674/why-is-php-printing-my-number-in-scientific-notation-when-i-specified-it-as-00
                return rtrim(rtrim(sprintf('%.8F', $temp[0] * pow(10, $temp[1])), '0'), '.');
            }
        }
        // handle basic numbers
        $val = preg_replace('/[^0-9\.-]/i', '', $val);
        if (is_numeric($val)) {
            $res = trim($val * 1.0);
            if ($res) {
                return $res;
            }
        }
        return $val;
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
        $res = trim(preg_replace('/[^0-9]/i', '', $val) * 1.0);
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
     * @return string
     */
    public static function GetPlaceholders($count, string $str = '{{}}'): string
    {
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

    private static function RowToJSON($row)
    {
        if (!is_object($row)) {
            return $row;
        }

        if ($row instanceof DateTime) {
            return Dates::SolrTime($row);
        }

        if ($row instanceof strongType || $row instanceof SQL_Base) {
            $json = $row->toArray(); // note: it's really annoying in testing to exclude empty values
            foreach ($json as $k => $v) {
                if ($k[0] == '_') {
                    unset($json[$k]);
                }
            }
            return $json;
        }

        if ($row instanceof stdClass) {
            return get_object_vars($row);
        }

        dd([
            'error' => 'fix_json unknown object',
            'class' => get_class($row),
            'strongType' => $row instanceof strongType,
            'row' => $row,
        ]);

    }

    /**
     * @param $json
     * @return array|null
     */
    public static function FixJSON($json): ?array
    {
        if (!is_array($json)) {
            $json = self::RowToJSON($json);
        }

        if (!is_array($json)) {
            exit(print_r($json, true));
        }

        foreach ($json as $i => $row) {
            if (is_object($row)) {
                $row = Strings::FixJSON(self::RowToJSON($row));
            }
            if (is_array($row)) {
                $json[$i] = Strings::FixJSON($row);
            } elseif (mb_detect_encoding($row)) {
                $json[$i] = is_numeric($row) ? $row : mb_convert_encoding($row, 'UTF-8', 'UTF-8');
            } else {
                $json[$i] = Strings::KeyboardOnly($row);
            }

        }
        return $json;
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
        $text = str_replace("\r", '', $text);
        $t = explode("\n", $text);
        return implode('<br/>', $t);
    }


    /**
     * @param        $num
     * @param int $dec
     * @param string $null
     *
     * @return string|null
     */
    public static function SmartNumberFormat($num, int $dec = 2, string $null = '-'): ?string
    {
        if (!is_numeric($dec))
            return $num;

        if (!is_numeric($num) || !$num)
            return $null;
        return number_format($num, $dec);
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
     * @return string
     */
    public static function StringRepeatCS(string $pattern, int $multiplier, string $separator = ','): string
    {
        $t = [];
        for ($j = 0; $j < $multiplier; $j++) {
            $t[] = $pattern;
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
     * @param $array
     * @param $parents
     * @param $dest
     * @return void
     */
    public static function FlattenArray($array, $parents = null, &$dest = null): void
    {
        foreach ($array as $k => $v) {

            $k = preg_replace('/[^a-z0-9]/i', '', $k);

            if (!is_array($v)) {
                $dest[$parents . $k] = $v;
                continue;
            }
            self::FlattenArray($v, $parents . $k . '_', $dest);
        }
    }
}

