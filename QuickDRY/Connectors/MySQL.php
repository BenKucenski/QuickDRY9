<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

use QuickDRY\Utilities\Strings;
use QuickDRY\Utilities\strongType;

/**
 * Class MySQL
 */
class MySQL extends strongType
{
    /**
     * @param $conn
     * @param $sql
     * @param $params
     *
     * @return array|string|string[]|null
     */
    public static function EscapeQuery($conn, $sql, $params): array|string|null
    {

        if (is_null($conn)) {
            Exception('QuickDRY Error: No MySQL Connection');
        }
        $matches = [];
        preg_match_all('/[\'\"][^\"\']*[\'\"](*SKIP)(*FAIL)|[:@](\w+[^\s+\,\;)])/i', $sql . ' ', $matches);

        if (sizeof($matches[1])) {
            Strings::SortArrayByValueLength($matches[1]);

            foreach ($matches[1] as $ph) {
                $sql = str_replace(':' . $ph, '{{' . $ph . '}}', $sql);
            }
        }

        $count = 0;
        return preg_replace_callback('/{{(.*?)}}/i', function ($result)
        use ($params, &$count, $conn, $sql) {
            if (isset($result[1])) {

                if (isset($params[$count])) {
                    $count++;
                    if ($result[1] !== 'nq') {
                        if($params[$count - 1] === 'null') {
                            return mysqli_escape_string($conn, $params[$count - 1]);
                        }
                        if(is_bool($params[$count - 1])) {
                            return $params[$count - 1];
                        }
                        return '\'' . mysqli_escape_string($conn, (string)$params[$count - 1]) . '\'';
                    } else {
                        return $params[$count - 1]; // don't use mysqli_escape_string here because it will escape quotes which breaks things
                    }
                }

                if (isset($params[$result[1]])) {
                    if (is_array($params[$result[1]])) {

                        die(print_r(['Error: Parameter cannot be array', $params], true));
                    }
                    if($params[$result[1]] === 'null') {
                        return mysqli_escape_string($conn, $params[$result[1]]);
                    }
                    return '\'' . mysqli_escape_string($conn, (string)$params[$result[1]]) . '\'';
                }

                if(is_null($params[$result[1]] ?? null)) {
                    return 'null';
                }

                Exception([$sql, $params], $result[0] . ' does not having a matching parameter (mysql_escape_query).');
            }
            return null;
        }, $sql);
    }

    /**
     * @param $input
     * @param bool $hex
     * @return string
     */
    public static function PasswordHash($input, bool $hex = true): string
    {
        $sha1_stage1 = sha1($input, true);
        $output = sha1($sha1_stage1, !$hex);
        return '*' . strtoupper($output);
    }
}





