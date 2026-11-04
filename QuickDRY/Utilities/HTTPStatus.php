<?php
declare(strict_types=1);

namespace QuickDRY\Utilities;

/**
 * Class HTTPStatus
 */
class HTTPStatus extends strongType
{
    /**
     * @param $http_status_code
     * @return null|string
     */
    public static function GetDescription($http_status_code): ?string
    {
        if (!is_numeric($http_status_code)) {
            Exception('QuickDRY Error: Invalid status code: ' . $http_status_code);
        }

        switch ($http_status_code) {
            case HTTP::HTTP_STATUS_OK:
                return 'OK';
            case HTTP::HTTP_STATUS_NOT_MODIFIED:
                return 'Not Modified';
            case HTTP::HTTP_STATUS_BAD_REQUEST:
                return 'Bad Request';
            case HTTP::HTTP_STATUS_UNAUTHORIZED:
                return 'Unauthorized';
            case HTTP::HTTP_STATUS_FORBIDDEN:
                return 'Forbidden';
            case HTTP::HTTP_STATUS_NOT_FOUND:
                return 'Not Found';
            case HTTP::HTTP_STATUS_NOT_ACCEPTABLE:
                return 'Not Acceptable';
            case HTTP::HTTP_STATUS_GONE:
                return 'Gone';
            case HTTP::HTTP_STATUS_CALM_DOWN:
                return 'Calm Your Scripts';
            case HTTP::HTTP_STATUS_UNPROCESSABLE_ENTITY:
                return 'Unprocessable Entity';
            case HTTP::HTTP_STATUS_TOO_MANY_REQUESTS:
                return 'Too Many Requests';
            case HTTP::HTTP_STATUS_INTERNAL_SERVER_ERROR:
                return 'Internal Server Error';
            case HTTP::HTTP_STATUS_BAD_GATEWAY:
                return 'Bad Gateway';
            case HTTP::HTTP_STATUS_SERVICE_UNAVAILABLE:
                return 'Service Unavailable';
            case HTTP::HTTP_STATUS_GATEWAY_TIMEOUT:
                return 'Gateway timeout';

        }

        Exception('QuickDRY Error: Invalid status code: ' . $http_status_code);
    }
}