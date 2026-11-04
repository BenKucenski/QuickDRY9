<?php
declare(strict_types=1);

namespace QuickDRY\JSON;

use QuickDRY\Utilities\HTTP;
use QuickDRY\Utilities\strongType;

/**
 * Class JsonStatusResult
 */
class JsonStatusResult extends strongType
{
    /****************************************************************************************
     *
     * Sample RFC-7807 compliant response
     *
     * {
     * "type": "https://tools.ietf.org/html/rfc7231#Section-6.6.1",
     * "title": "Internal Server Error",
     * "status": 500,
     * "traceId": "80000059-0001-f700-b63f-84710c7967bb",
     * "detail": "Internal server error."
     * }
     ******************************************************************************************/

    public ?string $type = null;
    public ?string $title = null;
    public ?int $status = null;
    public ?string $traceId = null;
    public ?string $detail = null;
    public ?string $errors = null;
    public ?int $statusCode = null;

    /**
     * @param $value
     * @param string|null $traceId
     * @return JsonStatusResult
     */
    public static function Success($value, ?string $traceId = null): JsonStatusResult
    {
        return JsonStatusResult::Create(HTTP::HTTP_STATUS_OK, $value, $traceId);
    }

    /**
     * @param $value
     * @param string|null $traceId
     */
    public static function Added($value, ?string $traceId = null)
    {
    }

    /**
     * @param int $HTTP_RESPONSE_CODE
     * @param string $Detail
     * @param string|null $traceId
     * @return JsonStatusResult
     */
    public static function Create(int $HTTP_RESPONSE_CODE, string $Detail, ?string $traceId = null): JsonStatusResult
    {
        /********************************************************************************
         *
         * 1xx Informational – the request was received, continuing process
         * 2xx Successful – the request was successfully received, understood and accepted
         * 3xx Redirection – further action needs to be taken in order to complete the request
         * 4xx Client Error – the request contains bad syntax or cannot be fulfilled
         * 5xx Server Error – the server failed to fulfill an apparently valid request
         **********************************************************************************/

        $error = new self();
        $error->status = $HTTP_RESPONSE_CODE;
        $error->detail = $Detail;
        $error->traceId = $traceId;
        self::SetResponseCode($HTTP_RESPONSE_CODE, $error);
        return $error;
    }

    /**
     * @param $HTTP_RESPONSE_CODE
     * @param $Errors
     * @param string|null $traceId
     * @return JsonStatusResult
     */
    public static function CreateError($HTTP_RESPONSE_CODE, $Errors, ?string $traceId = null): JsonStatusResult
    {
        /********************************************************************************
         *
         * 1xx Informational – the request was received, continuing process
         * 2xx Successful – the request was successfully received, understood and accepted
         * 3xx Redirection – further action needs to be taken in order to complete the request
         * 4xx Client Error – the request contains bad syntax or cannot be fulfilled
         * 5xx Server Error – the server failed to fulfill an apparently valid request
         **********************************************************************************/

        $error = new self();
        $error->status = $HTTP_RESPONSE_CODE;
        $error->errors = $Errors;
        $error->traceId = $traceId;
        self::SetResponseCode($HTTP_RESPONSE_CODE, $error);
        return $error;
    }


    /**
     * @param $HTTP_RESPONSE_CODE
     * @param JsonStatusResult $error
     */
    public static function SetResponseCode($HTTP_RESPONSE_CODE, self $error): void
    {
        switch ($HTTP_RESPONSE_CODE) {
            case HTTP::HTTP_STATUS_CONTINUE:
            {
                $error->title = 'Continue';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.2.1';
                break;
            }
            case HTTP::HTTP_STATUS_SWITCHING_PROTOCOLS:
            {
                $error->title = 'Switching Protocols';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.2.2';
                break;
            }
            case HTTP::HTTP_STATUS_OK:
            {
                $error->title = 'OK';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.3.1';
                break;
            }
            case HTTP::HTTP_STATUS_CREATED:
            {
                $error->title = 'Created';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.3.2';
                break;
            }
            case HTTP::HTTP_STATUS_ACCEPTED:
            {
                $error->title = 'Accepted';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.3.3';
                break;
            }
            case HTTP::HTTP_STATUS_NON_AUTHORITATIVE_INFORMATION:
            {
                $error->title = 'Non-Authoritative Information';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section6.3.4';
                break;
            }
            case HTTP::HTTP_STATUS_NO_CONTENT:
            {
                $error->title = 'No Content';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.3.5';
                break;
            }
            case HTTP::HTTP_STATUS_RESET_CONTENT:
            {
                $error->title = 'Reset Content';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.3.6';
                break;
            }
            case HTTP::HTTP_STATUS_PARTIAL_CONTENT:
            {
                $error->title = 'Partial Content';
                $error->type = 'https://tools.ietf.org/html/rfc7233#Section-4.1';
                break;
            }
            case HTTP::HTTP_STATUS_MULTIPLE_CHOICES:
            {
                $error->title = 'Multiple Choices';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.4.1';
                break;
            }
            case HTTP::HTTP_STATUS_MOVED_PERMANENTLY:
            {
                $error->title = 'Moved Permanently';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.4.2';
                break;
            }
            case HTTP::HTTP_STATUS_FOUND:
            {
                $error->title = 'Found';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.4.3';
                break;
            }
            case HTTP::HTTP_STATUS_SEE_OTHER:
            {
                $error->title = 'See Other';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.4.4';
                break;
            }
            case HTTP::HTTP_STATUS_NOT_MODIFIED:
            {
                $error->title = 'Not Modified';
                $error->type = 'https://tools.ietf.org/html/rfc7232#Section-4.1';
                break;
            }

            case HTTP::HTTP_STATUS_USE_PROXY:
            {
                $error->title = 'Use Proxy';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.4.5';
                break;
            }
            case HTTP::HTTP_STATUS_TEMPORARY_REDIRECT:
            {
                $error->title = 'Temporary Redirect';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.4.7';
                break;
            }
            case HTTP::HTTP_STATUS_BAD_REQUEST:
            {
                $error->title = 'Bad Request';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.1';
                break;
            }
            case HTTP::HTTP_STATUS_UNAUTHORIZED:
            {
                $error->title = 'Unauthorized';
                $error->type = 'https://tools.ietf.org/html/rfc7235#Section-3.1';
                break;
            }
            case HTTP::HTTP_STATUS_PAYMENT_REQUIRED:
            {
                $error->title = 'Payment Required';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.2';
                break;
            }
            case HTTP::HTTP_STATUS_FORBIDDEN:
            {
                $error->title = 'Forbidden';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.3';
                break;
            }
            case HTTP::HTTP_STATUS_NOT_FOUND:
            {
                $error->title = 'Not Found';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.4';
                break;
            }
            case HTTP::HTTP_STATUS_METHOD_NOT_ALLOWED:
            {
                $error->title = 'Method Not Allowed';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.5';
                break;
            }
            case HTTP::HTTP_STATUS_NOT_ACCEPTABLE:
            {
                $error->title = 'Not Acceptable';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.6';
                break;
            }
            case HTTP::HTTP_STATUS_PROXY_AUTHENTICATION_REQUIRED:
            {
                $error->title = 'Proxy Authentication Required';
                $error->type = 'https://tools.ietf.org/html/rfc7235#Section3.2';
                break;
            }
            case HTTP::HTTP_STATUS_REQUEST_TIMEOUT:
            {
                $error->title = 'Request Timeout';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.7';
                break;
            }
            case HTTP::HTTP_STATUS_CONFLICT:
            {
                $error->title = 'Conflict';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.8';
                break;
            }
            case HTTP::HTTP_STATUS_GONE:
            {
                $error->title = 'Gone';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.9';
                break;
            }
            case HTTP::HTTP_STATUS_LENGTH_REQUIRED:
            {
                $error->title = 'Length Required';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.10';
                break;
            }
            case HTTP::HTTP_STATUS_PRECONDITION_FAILED:
            {
                $error->title = 'Precondition Failed';
                $error->type = 'https://tools.ietf.org/html/rfc7232#Section-4.2';
                break;
            }

            case HTTP::HTTP_STATUS_PAYLOAD_TOO_LARGE:
            {
                $error->title = 'Payload Too Large';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.11';
                break;
            }
            case HTTP::HTTP_STATUS_URI_TOO_LONG:
            {
                $error->title = 'URI Too Long';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.12';
                break;
            }
            case HTTP::HTTP_STATUS_UNSUPPORTED_MEDIA_TYPE:
            {
                $error->title = 'Unsupported Media Type';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.13';
                break;
            }
            case HTTP::HTTP_STATUS_RANGE_NOT_SATISFIABLE:
            {
                $error->title = 'Range Not Satisfiable';
                $error->type = 'https://tools.ietf.org/html/rfc7233#Section-4.4';
                break;
            }
            case HTTP::HTTP_STATUS_EXPECTATION_FAILED:
            {
                $error->title = 'Expectation Failed';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.14';
                break;
            }
            case HTTP::HTTP_STATUS_UPGRADE_REQUIRED:
            {
                $error->title = 'Upgrade Required';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.5.15';
                break;
            }
            case HTTP::HTTP_STATUS_INTERNAL_SERVER_ERROR:
            {
                $error->title = 'Internal Server Error';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.6.1';
                break;
            }
            case HTTP::HTTP_STATUS_NOT_IMPLEMENTED:
            {
                $error->title = 'Not Implemented';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.6.2';
                break;
            }
            case HTTP::HTTP_STATUS_BAD_GATEWAY:
            {
                $error->title = 'Bad Gateway';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.6.3';
                break;
            }
            case HTTP::HTTP_STATUS_SERVICE_UNAVAILABLE:
            {
                $error->title = 'Service Unavailable';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.6.4';
                break;
            }
            case HTTP::HTTP_STATUS_GATEWAY_TIMEOUT:
            {
                $error->title = 'Gateway Timeout';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.6.5';
                break;
            }
            case HTTP::HTTP_STATUS_HTTP_VERSION_NOT_SUPPORTED:
            {
                $error->title = 'HTTP Version Not Supported';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.6.6';
                break;
            }
            default:
            {
                $error->title = 'Internal Server Error';
                $error->type = 'https://tools.ietf.org/html/rfc7231#Section-6.6.1';
                $error->statusCode = HTTP::HTTP_STATUS_INTERNAL_SERVER_ERROR;
                break;
            }

        }
    }
}