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
     * @param float $expires hours until it expires; 0 for a session cookie
     */
    public static function Set(string $name, string $value, float $expires = 24): void
    {
        // setcookie() needs an int timestamp; hours can be fractional
        $expires_at = $expires ? time() + (int)round($expires * 60 * 60) : 0;

        setcookie($name, $value, $expires_at, '/', HTTP_HOST);
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

        // same path and domain as Set(), or the browser keeps the cookie
        setcookie($name, '', time() - 3600, '/', HTTP_HOST);
        unset(Cookie::$_VALS[$name]);
        unset($_COOKIE[$name]);
    }
}
