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
     * Proxies whose forwarding headers are believed (IPs or CIDR ranges, IPv4 or IPv6).
     *
     * Only when the direct peer (REMOTE_ADDR) is one of these are CF-Connecting-IP /
     * X-Forwarded-For read; otherwise anyone could send those headers and claim any
     * address. Defaults to loopback, which covers a reverse proxy or tunnel on the same
     * machine (e.g. cloudflared, IIS ARR). Add a load balancer's range here if the app
     * sits behind one on another host.
     *
     * @var string[]
     */
    public static array $TrustedProxies = ['127.0.0.0/8', '::1/128'];

    /**
     * The client's IP address, normalized (IPv6 lowercase and compressed,
     * IPv4-mapped IPv6 like ::ffff:1.2.3.4 returned as 1.2.3.4).
     *
     * When the request came through a trusted proxy (see $TrustedProxies):
     *   1. CF-Connecting-IP, if valid (Cloudflare sets it to the visitor's address)
     *   2. X-Forwarded-For, read right to left, skipping trusted proxies; the first
     *      address that isn't one is the client. (The left end is whatever the client
     *      sent and can't be trusted.)
     * Otherwise, or if neither header helps, REMOTE_ADDR.
     *
     * @return string|null
     */
    public static function REMOTE_ADDR(): ?string
    {
        $remote = self::NormalizeIP($_SERVER['REMOTE_ADDR'] ?? null);
        if ($remote === null || !self::IsTrustedProxy($remote)) {
            return $remote;
        }

        $cf = self::NormalizeIP($_SERVER['HTTP_CF_CONNECTING_IP'] ?? null);
        if ($cf !== null) {
            return $cf;
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $chain = array_reverse(explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']));
            foreach ($chain as $hop) {
                $ip = self::NormalizeIP($hop);
                if ($ip === null) {
                    break; // garbage in the chain; don't trust anything to the left of it
                }
                if (!self::IsTrustedProxy($ip)) {
                    return $ip;
                }
            }
        }

        return $remote;
    }

    /**
     * Validates and normalizes an IP address. Accepts "[::1]", "[::1]:443" and
     * "1.2.3.4:80" forms some proxies send. Returns null if it isn't an IP.
     *
     * @param string|null $ip
     * @return string|null
     */
    public static function NormalizeIP(?string $ip): ?string
    {
        $ip = trim((string)$ip);
        if ($ip === '') {
            return null;
        }

        if (preg_match('/^\[([^\]]+)\](?::\d+)?$/', $ip, $m)) {
            $ip = $m[1];                                  // [v6] or [v6]:port
        } elseif (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $ip, $m)) {
            $ip = $m[1];                                  // v4:port
        }

        $ip = preg_replace('/%.*$/', '', $ip);            // zone id, e.g. fe80::1%eth0

        $bin = @inet_pton($ip);
        if ($bin === false || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        if (strlen($bin) === 16 && str_starts_with($bin, str_repeat("\0", 10) . "\xff\xff")) {
            return inet_ntop(substr($bin, 12));           // ::ffff:a.b.c.d -> a.b.c.d
        }

        return inet_ntop($bin);
    }

    /**
     * @param string $ip a normalized IP
     * @return bool
     */
    public static function IsTrustedProxy(string $ip): bool
    {
        foreach (self::$TrustedProxies as $range) {
            if (self::IPInRange($ip, $range)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string $ip
     * @param string $range an IP or CIDR, e.g. 10.0.0.0/8 or 2001:db8::/32
     * @return bool
     */
    public static function IPInRange(string $ip, string $range): bool
    {
        [$net, $bits] = array_pad(explode('/', trim($range), 2), 2, null);
        $ipBin = @inet_pton($ip);
        $netBin = @inet_pton((string)$net);
        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8;
        $bits = $bits === null ? $maxBits : (int)$bits;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        if (substr($ipBin, 0, $bytes) !== substr($netBin, 0, $bytes)) {
            return false;
        }
        $rem = $bits % 8;
        if ($rem === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rem)) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($netBin[$bytes]) & $mask);
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
