<?php

namespace Bkucenski\Quickdry\Web;

class Request
{
    public static function Get(string $name)
    {
        return $_REQUEST[$name] ?? null;
    }

    public static function isset(string $name): bool
    {
        return isset($_REQUEST[$name]);
    }
}