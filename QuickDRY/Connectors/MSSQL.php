<?php
declare(strict_types=1);

namespace QuickDRY\Connectors;

/** DO NOT USE THIS CLASS DIRECTLY **/

use DateTime;
use InvalidArgumentException;
use QuickDRY\Utilities\Dates;
use QuickDRY\Utilities\Strings;
use QuickDRY\Utilities\strongType;

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
            Exception($data);
        }
        if (is_numeric($data)) return "'" . $data . "'";

        if ($data instanceof DateTime) {
            $data = Dates::Timestamp($data);
        }

        $non_displayables = [
            // '/%0[0-8bcef]/',            // breaks LIKE '%001'
            // '/%1[0-9a-f]/',
            '/[\x00-\x08]/',            // 00-08
            '/\x0b/',                   // 11
            '/\x0c/',                   // 12
            '/[\x0e-\x1f]/'             // 14-31
        ];

        if ($data) {
            foreach ($non_displayables as $regex) {
                $data = preg_replace($regex, '', (string)$data);
            }
        }

        $data = str_replace("'", "''", $data ?? 'null');
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
        // Require associative array: ['name' => value]
        if ($params === null) {
            $params = [];
        }
        if (!is_array($params)) {
            throw new InvalidArgumentException('EscapeQuery expects $params as associative array of [name => value].');
        }
        foreach ($params as $k => $_) {
            if (!is_string($k)) {
                throw new InvalidArgumentException('EscapeQuery parameter keys must be strings like "TabName". ' . json_encode(['query' => $sql, 'params' => $params]) . '.');
            }
            if ($k === '' || $k[0] === '@' || $k[0] === ':') {
                throw new InvalidArgumentException('Invalid parameter key "' . $k . '". Use keys without leading @ or : (e.g., ["TabName" => "Foreclosure"]).');
            }
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $k)) {
                throw new InvalidArgumentException('Invalid parameter key "' . $k . '". Allowed: letters, digits, underscore; must not start with a digit.');
            }
        }

        /**
         * Pattern behavior:
         *  - Eats quoted strings first (single or double) so we don't replace inside them.
         *  - Then matches only named placeholders (captures name in group 1).
         *  - Guards against @@ (e.g., @@ROWCOUNT) via (?<!@).
         *  - Also avoids matching when @ is part of an identifier by requiring it not be preceded by [A-Za-z0-9_].
         */
        $pattern = '/\'(?:\'\'|[^\'])*\'|"(?:""|[^"])*"|(?<![@A-Za-z0-9_])@([A-Za-z_][A-Za-z0-9_]*)/';

        if ($test) {
            $matches = [];
            preg_match_all($pattern, $sql, $matches);
            Testing($matches);
        }

        return preg_replace_callback($pattern, function ($m) use ($params) {
            $tok = $m[0];

            // Quoted literal? return unchanged
            $c0 = $tok[0];
            if ($c0 === "'" || $c0 === '"') {
                return $tok;
            }

            // @name placeholder
            $name = $m[1];

            // Bind only if $params has a key exactly matching $name (no @ or :)
            if (array_key_exists($name, $params)) {
                $val = $params[$name];

                // Allow "_NQ" suffix to mean "no quotes" if you use that convention
                if (Strings::EndsWith($name, '_NQ')) {
                    return (string)$val;
                }

                if ($val === null) {
                    return 'null';
                }
                return MSSQL::EscapeString($val);
            }

            // Not provided -> leave as a T-SQL variable
            return $tok;
        }, $sql);
    }


}
