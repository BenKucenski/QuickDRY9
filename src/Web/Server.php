<?php

namespace Bkucenski\Quickdry\Web;

/**
 *
 */
class Server
{
    /**
     * @param array|null $remove
     * @return string
     */
    public static function GetQueryString(?array $remove): string
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
}