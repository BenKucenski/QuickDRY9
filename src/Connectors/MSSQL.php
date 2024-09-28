<?php

namespace Bkucenski\Quickdry\Connectors;

/** DO NOT USE THIS CLASS DIRECTLY **/

use DateTime;
use Bkucenski\Quickdry\Utilities\Dates;
use Bkucenski\Quickdry\Utilities\Debug;
use Bkucenski\Quickdry\Utilities\strongType;
use Bkucenski\Quickdry\Utilities\Strings;

const GUID_MSSQL = 'UPPER(SUBSTRING(master.dbo.fn_varbintohexstr(HASHBYTES(\'MD5\',cast(NEWID() as varchar(36)))), 3, 32)) ';

/**
 * Class MSSQL
 */
class MSSQL extends strongType
{
    /**
     * @param $data
     *
     * @return string
     */
    public static function EscapeString($data): string
    {
        if (is_array($data)) {
            Debug::Halt($data);
        }
        if (is_numeric($data)) return "'" . $data . "'";

        if ($data instanceof DateTime) {
            $data = Dates::Timestamp($data);
        }

        $non_displayables = [
//            '/%0[0-8bcef]/',            // url encoded 00-08, 11, 12, 14, 15 // this breaks LIKE '%001' for example
//            '/%1[0-9a-f]/',             // url encoded 16-31
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        ];
        foreach ($non_displayables as $regex) {
            $data = preg_replace($regex, '', $data);
        }
        $data = str_replace("'", "''", $data);
        if (strcasecmp($data, 'null') == 0) {
            return 'null';
        }

        $data = str_replace('{{{', '', $data);
        $data = str_replace('}}}', '', $data);

        return "'" . $data . "'";
    }

    /**
     * @param $sql
     * @param $params
     * @param bool $test
     * @return string
     */
    public static function EscapeQuery($sql, $params, bool $test = false): string
    {
        $pattern = '/[@:]([\w\d]*)?/si';
        if ($test) {
            $matches = [];
            preg_match_all($pattern, $sql, $matches);
            Debug::Halt($matches);
        }
        $count = 0;
        return preg_replace_callback($pattern, function ($result)
        use ($params, &$count, $sql) {
            if (isset($result[1])) {
                if (isset($params[$count])) {
                    $count++;
                    switch ($result[1]) {
                        case 'nullstring':
                            if (!$params[$count - 1] || $params[$count - 1] === 'null') {
                                return 'null';
                            }
                            return MSSQL::EscapeString($params[$count - 1]);

                        case 'nullnumeric':
                            if (!$params[$count - 1] || $params[$count - 1] === 'null') {
                                return 'null';
                            }
                            return $params[$count - 1] * 1.0;

                        case 'nq':
                            return $params[$count - 1];

                        default:
                            return MSSQL::EscapeString($params[$count - 1]);
                    }
                }

                if (isset($params[$result[1]])) {
                    if (Strings::EndsWith($result[1], '_NQ')) {
                        return $params[$result[1]];
                    }
                    return MSSQL::EscapeString($params[$result[1]]);
                } else {
                    if(is_null($params[$result[1]])) {
                        return 'null';
                    }
                    // in order to allow more advanced queries that Declare Variables, we just need to ignore @Var if it's not in the passed in parameters
                    // SQL Server will return an error if there really is one
                    return '@' . $result[1];
                }
                //throw new Exception(print_r([json_encode($params, JSON_PRETTY_PRINT), $count, $result, $sql], true) . ' does not have a matching parameter (ms_escape_query).');
            }
            return '';
        }, $sql);
    }
}
