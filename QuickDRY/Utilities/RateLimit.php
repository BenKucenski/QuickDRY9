<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

use QuickDRY\Web\Server;

/**
 * Sliding-window rate limits, e.g. for sign-in, sign-up, password reset and MFA codes:
 *
 *     if (RateLimit::TooMany('login_email', $email, 10, 900)) {
 *         HTTP::RedirectError(RateLimit::MESSAGE);
 *     }
 *     if (!$user) {
 *         RateLimit::Hit('login_email', $email, 900);
 *     } else {
 *         RateLimit::Clear('login_email', $email);
 *     }
 *
 * Each bucket+key keeps its recent hit times in a small file under $Folder (the system temp
 * folder by default), so it suits a single web server. If the folder can't be written, limits
 * are skipped rather than locking everyone out.
 */
class RateLimit
{
    public const string MESSAGE = 'Too many attempts. Please wait a few minutes and try again.';

    /** Where the counters live; null = <system temp>/quickdry_rate_limit */
    public static ?string $Folder = null;

    public static function IP(): string
    {
        return (string)(Server::REMOTE_ADDR() ?? 'unknown');
    }

    /**
     * Has this key already used up its attempts?
     */
    public static function TooMany(string $bucket, string $key, int $max, int $window_seconds): bool
    {
        return count(self::Read($bucket, $key, $window_seconds)) >= $max;
    }

    /**
     * Record one attempt.
     */
    public static function Hit(string $bucket, string $key, int $window_seconds): void
    {
        $file = self::File($bucket, $key);
        if (!$file) {
            return;
        }
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return;
        }
        if (flock($fp, LOCK_EX)) {
            $hits = self::Recent((string)stream_get_contents($fp), $window_seconds);
            $hits[] = time();
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($hits));
            fflush($fp);
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }

    /**
     * Forget a key's attempts (e.g. after a successful sign-in).
     */
    public static function Clear(string $bucket, string $key): void
    {
        $file = self::File($bucket, $key);
        if ($file && is_file($file)) {
            @unlink($file);
        }
    }

    /** @return int[] */
    private static function Read(string $bucket, string $key, int $window_seconds): array
    {
        $file = self::File($bucket, $key);
        if (!$file || !is_file($file)) {
            return [];
        }
        return self::Recent((string)@file_get_contents($file), $window_seconds);
    }

    /** @return int[] */
    private static function Recent(string $json, int $window_seconds): array
    {
        $hits = json_decode($json, true);
        if (!is_array($hits)) {
            return [];
        }
        $since = time() - $window_seconds;
        return array_values(array_filter($hits, fn($t) => is_int($t) && $t > $since));
    }

    private static function File(string $bucket, string $key): ?string
    {
        $dir = self::$Folder ?? rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'quickdry_rate_limit';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return null;
        }
        if (!is_writable($dir)) {
            return null;
        }
        return $dir . DIRECTORY_SEPARATOR . hash('sha256', $bucket . '|' . strtolower(trim($key))) . '.json';
    }
}
