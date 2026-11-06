<?php
declare(strict_types=1);

namespace models;

use QuickDRY\Interfaces\ICurlLog;

/**
 *
 */
class CurlLog implements ICurlLog
{

    /**
     * @param string $path
     * @param array|null $params
     * @param float $duration
     * @param string $method
     * @return void
     */
    public static function Log(string $path, ?array $params, float $duration, string $method): void
    {
        // TODO: Implement Log() method.
    }
}