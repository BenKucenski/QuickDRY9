<?php
declare(strict_types=1);

namespace QuickDRY\Interfaces;

/**
 *
 */
interface ICurlLog
{
    /**
     * @param string $path
     * @param array|null $params
     * @param float $duration
     * @param string $method
     * @return void
     */
    public static function Log(
        string $path,
        ?array $params,
        float  $duration,
        string $method
    ): void;
}