<?php
declare(strict_types=1);

namespace QuickDRY\Web;

use QuickDRY\Utilities\HTTP;

/**
 *
 */
class Request
{
    /**
     * @return array
     */
    public static function toArray(): array
    {
        $vals = array_map(function ($v) {
            return $v;
        }, $_POST);
        foreach ($_GET as $k => $v) {
            $vals[$k] = $v;
        }

        return $vals;
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public static function Get(string $name): mixed
    {
        return $_REQUEST[$name] ?? null;
    }

    /**
     * @param string $name
     * @param $value
     * @return void
     */
    public static function Set(string $name, $value): void
    {
        $_REQUEST[$name] = $value;
        $_POST[$name] = $value;
        $_GET[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isset(string $name): bool
    {
        return isset($_REQUEST[$name]);
    }

    /**
     * @param $serialized
     */
    public static function FromSerialized($serialized): void
    {
        if (!$serialized) {
            return;
        }

        $post = HTTP::PostFromSerialized($serialized);
        foreach ($post as $k => $v) {
            self::Set($k, $v);
        }
    }
}