<?php
declare(strict_types=1);

namespace QuickDRY\Web;

use QuickDRY\Utilities\strongType;

/**
 * Class Cookie
 */
class Cookie extends strongType
{
    private static array $_VALS = [];

    /**
     * @param string $name
     * @param string $value
     * @param float $expires
     */
    public static function Set(string $name, string $value, float $expires = 24): void
    {
        if ($expires) {
            setcookie($name, $value, time() + $expires * 60 * 60, '/', HTTP_HOST);
        } else {
            setcookie($name, $value, 0, '/', HTTP_HOST);
        }
        Cookie::$_VALS[$name] = $value;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public static function Get(string $name): ?string
    {
        if (isset(Cookie::$_VALS[$name]))
            return Cookie::$_VALS[$name];
        if (isset($_COOKIE[$name]))
            return $_COOKIE[$name];
        return NULL;
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function isset($name): bool
    {
        return isset($_COOKIE[$name]) || isset(self::$_VALS[$name]);
    }

    /**
     * @param $name
     */
    public static function unset($name): void
    {
        if (!isset($_COOKIE[$name]))
            return;

        // http://petersnotes.blogspot.com/2011/01/iphone-cookie-hell.html
        setcookie($name, ''); // for iPhone
        setcookie($name, '', time() - 1, HTTP_HOST);
        unset(Cookie::$_VALS[$name]);
        unset($_COOKIE[$name]);
    }
}
