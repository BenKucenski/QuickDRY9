<?php
declare(strict_types=1);

namespace QuickDRY\Web;


/**
 *
 */
class Server
{
    /**
     * @param array|null $remove
     * @return string
     */
    public static function GetQueryString(?array $remove = null): string
    {
        $params = [];
        parse_str($_SERVER['QUERY_STRING'] ?? '', $params);
        if ($remove) {
            foreach ($remove as $key) {
                if (isset($params[$key])) {
                    unset($params[$key]);
                }
            }
        }
        return http_build_query($params);
    }

    /**
     * @return string|null
     */
    public static function REMOTE_ADDR(): ?string
    {
        $ip = null;

        // Check HTTP_X_FORWARDED_FOR
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // X-Forwarded-For can be a comma-separated list: client, proxy1, proxy2
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

            // Take the first valid IP in the list
            foreach ($forwardedIps as $forwardedIp) {
                $forwardedIp = trim($forwardedIp);
                if (filter_var($forwardedIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                    $ip = $forwardedIp;
                    break;
                }
            }
        }

        // Fallback to REMOTE_ADDR
        if (!$ip && !empty($_SERVER['REMOTE_ADDR'])) {
            if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }

        return $ip;
    }

    /**
     * @return string|null
     */
    public static function REQUEST_URI(): ?string
    {
        return explode('?', $_SERVER['REQUEST_URI'] ?? ($_SERVER['HTTP_X_ORIGINAL_URL'] ?? ''))[0];
    }


    /**
     * @return string|null
     */
    public static function REQUEST_METHOD(): ?string
    {
        return $_SERVER['REQUEST_METHOD'] ?? null;
    }

    /**
     * @return string|null
     */
    public static function HTTP_USER_AGENT(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * @return string|null
     */
    public static function QUERY_STRING(): ?string
    {
        return $_SERVER['QUERY_STRING'] ?? null;
    }

    /**
     * @return string|null
     */
    public static function HTTP_HOST(): ?string
    {
        return $_SERVER['HTTP_HOST'] ?? null;
    }

    /**
     * @return string
     */
    public static function getCurrentDomain(): string
    {
        // Determine if HTTPS is on
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443
            ? 'https'
            : 'http';

        // Get the host name
        $host = $_SERVER['HTTP_HOST'];

        // Combine and return
        return $scheme . '://' . $host;
    }
}
