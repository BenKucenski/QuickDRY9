<?php

namespace Bkucenski\Quickdry\Utilities;

/**
 * Class Network
 */
class Network
{
    private static ?string $interfaces = null;

    /**
     * @return null|string
     */
    public static function Interfaces(): ?string
    {
        if (!is_null(static::$interfaces)) {
            return static::$interfaces;
        }

        $ipRes = '';
        $ipPattern = '';

        switch (strtoupper(PHP_OS)) {
            case 'WINNT':
                $ipRes = shell_exec('ipconfig');
                $ipPattern = '/IPv4 Address[^:]+: ' . '([\d]{1,3}\.[\d]' . '{1,3}\.[\d]{1,3}' . '\.[\d]{1,3})/';
                break;

            case 'LINUX':
                $ipRes = shell_exec('/sbin/ifconfig');
                $ipPattern = '/inet addr:([\d]' . '{1,3}\.[\d]{1,3}' . '\.[\d]{1,3}\.' . '[\d]{1,3})/';
                break;

            default:
                break;
        }

        if (preg_match_all($ipPattern, $ipRes, $matches)) {
            static::$interfaces = json_encode($matches[1]);

            return static::$interfaces;
        }
        return null;
    }

    // https://mebsd.com/coding-snipits/php-ipcalc-coding-subnets-ip-addresses.html
    // convert cidr to netmask
    // e.g. 21 = 255.255.248.0

    /**
     * @param $cidr
     * @return bool|string
     */
    public static function cidr2netmask($cidr): bool|string
    {
        $bin = '';
        for ($i = 1; $i <= 32; $i++) {
            $bin .= $cidr >= $i ? '1' : '0';
        }

        $netmask = long2ip(bindec($bin));

        if ($netmask == '0.0.0.0') {
            return false;
        }

        return $netmask;
    }

    // get network address from cidr subnet
    // e.g. 10.0.2.56/21 = 10.0.0.0

    /**
     * @param $ip
     * @param $cidr
     * @return string
     */
    public static function cidr2network($ip, $cidr): string
    {
        return long2ip((ip2long($ip)) & ((-1 << (32 - (int)$cidr))));
    }

    // convert netmask to cidr
    // e.g. 255.255.255.128 = 25

    /**
     * @param $netmask
     * @return int
     */
    private static function netmask2cidr($netmask): int
    {
        $bits = 0;
        $netmask = explode('.', $netmask);

        foreach ($netmask as $octect) {
            $bits += strlen(str_replace('0', '', decbin($octect)));
        }

        return $bits;
    }

    // is ip in subnet
    // e.g. is 10.5.21.30 in 10.5.16.0/20 == true
    //      is 192.168.50.2 in 192.168.30.0/23 == false

    /**
     * @param $ip
     * @param $network
     * @param $cidr
     * @return bool
     */
    private static function cidr_match($ip, $network, $cidr): bool
    {
        if (!is_numeric($cidr)) {
            $cidr = static::netmask2cidr($cidr);
        }

        if ((ip2long($ip) & ~((1 << (32 - $cidr)) - 1)) == ip2long($network)) {
            return true;
        }

        return false;
    }

    /**
     * @param $ip
     * @param $valid_ips
     * @return bool
     */
    public static function ValidateIP($ip, $valid_ips): bool
    {
        foreach ($valid_ips as $valid_ip => $netmask) {
            if (Network::cidr_match($ip, $valid_ip, $netmask)) {
                return true;
            }
        }

        return false;
    }
}


