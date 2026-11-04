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
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function ApplyArray(string $var, array $data, string &$sql, array &$params): void
    {
        if(!sizeof($data)) {
            return; // leave the placeholder so the query doesn't break on null
        }
        $placeholders = Strings::GetPlaceholders(sizeof($data), '@' . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }

    /**
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function ApplyValues(string $var, array $data, string &$sql, array &$params): void
    {
        $placeholders = Strings::GetValues(sizeof($data), '@' . $var, true);
        $sql = str_replace('\'--' . $var . '--\'', $placeholders, $sql);
        foreach($data as $i => $k) {
            $params[$var . $i] = $k;
        }
    }

    /**
     * @param string $var
     * @param array $data
     * @param string $sql
     * @param array $params
     * @return void
     */
    public static function ApplyValuesArray(string $var, array $data, string &$sql, array &$params): void
    {
        $data2 = [];
        foreach($data as $i => $v) {
            $placeholders = Strings::GetValues(sizeof($v), '@' . $var . $i, true);
            $placeholders = str_replace('),(', ',', $placeholders);
            foreach($v as $j => $k) {
                $params[$var . $i . $j] = $k;
            }
            $data2[] = $placeholders;
        }
        $sql = str_replace('\'--' . $var . '--\'', implode(',', $data2), $sql);
    }
}