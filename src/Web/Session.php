<?php

namespace Bkucenski\Quickdry\Web;

/**
 * Class Session
 */
class Session
{
    public static function Get(string $name)
    {
        return $_SESSION[$name] ?? null;
    }

    public static function isset(string $name): bool
    {
        return isset($_SESSION[$name]);
    }
}

