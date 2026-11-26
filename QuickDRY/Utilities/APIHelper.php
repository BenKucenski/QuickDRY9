<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;


/**
 *
 */
class APIHelper
{
    public static ?string $default_placeholder = ':';

    public static array $_CACHE = [];

    // https://stackoverflow.com/questions/9690448/regular-expression-to-remove-comments-from-sql-statement
    /**
     * @param $sqlString
     * @return string
     */
    public static function removeSqlComment($sqlString): string
    {
        $regEx = [
            '~(?:".*?"|\'.*?\')(*SKIP)(*F)|--.*$~m',
            '~(?:".*?"|\'.*?\')(*SKIP)(*F)|/\*.*?\*/~s',
            '~^;?\R~m'
        ];
        return trim(preg_replace($regEx, '', $sqlString));
    }

    /**
     * @param string $filename
     * @param string|null $placeholder
     * @return string
     */
    public static function getSQL(string $filename, ?string $placeholder = null): string
    {
        $filename = realpath($filename);

        if(!isset(self::$_CACHE[$filename])){
            // MySQL SQL files expect @ when run in Workbench but PHP's mysqli expects :
            // for SQL Server, set the default placeholder to @ at the start of your scripts
            $placeholder = $placeholder ?: self::$default_placeholder;
            self::$_CACHE[$filename] = self::removeSqlComment(str_replace('@', $placeholder, file_get_contents($filename)));
        }

        return self::$_CACHE[$filename];
    }

    /**
     * The placeholder prefix the Apply* helpers write: the one getSQL() put in
     * place of "@" (pass the same override you passed to getSQL(), if any).
     * MySQL::EscapeQuery() only fills in ":name" and MSSQL::EscapeQuery() only
     * "@name", so a placeholder with the other prefix is never filled in: on
     * MySQL it becomes an empty user variable and the query quietly matches
     * nothing.
     *
     * @param string|null $placeholder
     * @return string
     */
    private static function prefix(?string $placeholder): string
    {
        return $placeholder ?: (self::$default_placeholder ?: '@');
    }

    /**
     * Replaces the marker '--{$var}--' (with its quotes) with one placeholder
     * per value, for an IN (...) list: IN ('--ids--') becomes IN (:ids0, :ids1).
     * An empty $data leaves the marker, so IN ('--ids--') matches nothing
     * instead of being a syntax error.
     *
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @param string|null $placeholder the prefix getSQL() used; default self::$default_placeholder
     * @return void
     */
    public static function ApplyArray(string $var, array $data, string &$sql, array &$params, ?string $placeholder = null): void
    {
        if(!sizeof($data)) {
            return; // leave the placeholder so the query doesn't break on null
        }
        $data = array_values($data);
        $placeholders = Strings::GetPlaceholders(sizeof($data), self::prefix($placeholder) . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }

    /**
     * Replaces the marker '--{$var}--' with one single-value row per value,
     * for VALUES lists: (:v0),(:v1).
     *
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @param string|null $placeholder the prefix getSQL() used; default self::$default_placeholder
     * @return void
     */
    public static function ApplyValues(string $var, array $data, string &$sql, array &$params, ?string $placeholder = null): void
    {
        $data = array_values($data);
        $placeholders = Strings::GetValues(sizeof($data), self::prefix($placeholder) . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }

    /**
     * Replaces the marker '--{$var}--' with one row per item of $data, each
     * row's values in order: (:v0_0,:v0_1),(:v1_0,:v1_1). Parameter names
     * separate row and column with "_", so row 1 column 11 (v1_11) can't
     * collide with row 11 column 1 (v11_1).
     *
     * @param string $var
     * @param array $data list of rows, each a list of values
     * @param string $sql
     * @param array $params
     * @param string|null $placeholder the prefix getSQL() used; default self::$default_placeholder
     * @return void
     */
    public static function ApplyValuesArray(string $var, array $data, string &$sql, array &$params, ?string $placeholder = null): void
    {
        $prefix = self::prefix($placeholder);
        $data2 = [];
        foreach(array_values($data) as $i => $v) {
            $v = array_values($v);
            $placeholders = Strings::GetValues(sizeof($v), $prefix . $var . $i . '_', true);
            $placeholders = str_replace('),(', ',', $placeholders);
            foreach($v as $j => $k) {
                $params[$var . $i . '_' . $j] = $k;
            }
            $data2[] = $placeholders;
        }
        $sql = str_replace('\'--' . $var . '--\'', implode(',', $data2), $sql);
    }
}