<?php

namespace Bkucenski\Quickdry\Web;

/**
 * Class Meta
 */
class Meta
{
    private static ?string $title = null;
    private static ?string $description = null;
    private static ?string $keywords = null;

    /**
     * @param string|null $val
     * @return null|string
     */
    public static function Title(string $val = null): ?string
    {
        if (is_null($val)) {
            if(!self::$title) {
                return '';
            }
            return ': ' . str_replace('"', '\\"', self::$title);
        }
        self::$title = $val;
        return $val;
    }

    /**
     * @param null $val
     * @return mixed|null
     */
    public static function Description($val = null): mixed
    {
        if (is_null($val))
            return str_replace('"', '\\"', self::$description);
        self::$description = $val;
        return $val;
    }

    /**
     * @param null $val
     * @return mixed|null
     */
    public static function Keywords($val = null): mixed
    {
        if (is_null($val))
            return str_replace('"', '\\"', self::$keywords);
        self::$keywords = $val;
        return $val;
    }
}

